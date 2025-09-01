<?php

namespace App\Http\Controllers;

use App\Models\CentreInteret;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Message;
use Carbon\Carbon;

use Illuminate\Support\Facades\DB;


use Illuminate\Validation\ValidationException;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\User;


class EventController extends Controller
{
    /************ LES PRIVATES CONST********/

    /**=========================
     * >> Limite fixée en dur ici
     * ========================**/

    private const MAX_ACTIVE_EVENTS = 10;
    /**************************************/


    /*############################## HELPER | LES PRIVATES FN ########################################*/

    /**=======================================
     * >> redirige en arrière avec un flash.
     * =======================================**/
    private function backWithFlash(string $message, string $level = 'error')
    {
        return back()->with('flash', [$level => $message]);
    }

    /**==================================================================
     * >> logique réutilisable pour savoir si un événement est déjà passé.
     * =================================================================**/
    private function eventIsPast(Event $event): bool
    {
        return $this->eventDateTime($event)->isPast();
    }


    private function eventDateTime(Event $event): Carbon
    {
        // $event->date peut être string ou Carbon ; on normalise en Carbon
        $base = $event->getAttribute('date') instanceof Carbon
            ? $event->getAttribute('date')->copy()
            : Carbon::parse($event->getAttribute('date'));

        $h = $event->hour ? (strlen($event->hour) === 5 ? $event->hour . ':00' : $event->hour) : '00:00:00';

        return $base->setTimeFromTimeString($h);
    }


    /**==============================================================================================
     * >>  Notifie par e-mail les participants d'un eventuel changement dans l'event (modif|annulation)
     * ==============================================================================================**/
    private function notifyParticipantsInline(Event $event, string $action, array $changes = []): void
    {
        try {
            // Charge les participants (Collection de User)
            $event->loadMissing('participants:id,email,pseudo');

            // Récupère bien les IDs des USERS, pas ceux de la pivot
            $recipientIds = $event->participants->pluck('id')->all();

            // Ajouter le créateur si ce n'est pas l'acteur courant
            if ($event->created_by !== auth()->id()) {
                $recipientIds[] = $event->created_by;
            }

            // Uniques, et on enlève l’acteur
            $recipientIds = array_values(array_unique(array_diff($recipientIds, [auth()->id()])));

            // En local, s'il n'y a personne d'autre, on s'envoie à soi pour tester
            if (empty($recipientIds) && app()->isLocal()) {
                $recipientIds = [auth()->id()];
            }

            // Si toujours vide, on log et on sort proprement
            if (empty($recipientIds)) {
                Log::info('Notify: aucun destinataire', ['event_id' => $event->id, 'action' => $action]);
                return;
            }

            // Récupère les emails
            $recipients = User::whereIn('id', $recipientIds)
                ->whereNotNull('email')
                ->get(['email', 'pseudo']);

            $subject = match ($action) {
                'cancelled' => "Événement annulé : {$event->name_event}",
                'deactivated' => "Événement désactivé : {$event->name_event}",
                default => "Événement mis à jour : {$event->name_event}",
            };

            $lines = [];
            $lines[] = $subject;
            $lines[] = "Lieu : {$event->location}";
            $lines[] = "Date : {$event->date} à {$event->hour}";
            if ($action === 'updated' && !empty($changes)) {
                $lines[] = "";
                $lines[] = "Changements :";
                foreach ($changes as $field => $diff) {
                    $old = (string)($diff['old'] ?? '');
                    $new = (string)($diff['new'] ?? '');
                    $lines[] = "- {$field} : {$old} → {$new}";
                }
            }
            $lines[] = "";
            $lines[] = "Voir l’événement : " . route('events.show', $event->id);
            $body = implode("\n", $lines);

            foreach ($recipients as $user) {
                Mail::raw($body, function ($message) use ($user, $subject) {
                    $message->to($user->email)
                        ->from(config('mail.from.address'), config('mail.from.name'))
                        ->subject($subject);
                });
            }

            Log::info('Notify: mails envoyés', [
                'event_id' => $event->id,
                'action' => $action,
                'count' => $recipients->count(),
                'ids' => $recipientIds,
            ]);

        } catch (\Throwable $e) {
            Log::error('notifyParticipantsInline failed', [
                'event_id' => $event->id,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /** ===========================================
     * >> Nombre d'événements ACTIFS| EN ATTENTE pour un user.
     * =============================================*/
    private function activeEventsCountFor(User $user): int
    {
        $today = now()->toDateString();
        $now = now()->format('H:i:s');

        return Event::where('created_by', $user->id)
            ->whereNull('cancelled_at')
            ->where(function ($q) {
                // ACTIFS
                $q->where('inactif', false)
                    // EN ATTENTE (pas encore revu par un admin)
                    ->orWhere(function ($q2) {
                        $q2->whereNull('validated_by_id')
                            ->where(function ($q3) {
                                $q3->whereNull('confirmed')->orWhere('confirmed', false);
                            });
                    });
            })
            // À VENIR uniquement
            ->where(function ($q) use ($today, $now) {
                $q->whereDate('date', '>', $today)
                    ->orWhere(function ($qq) use ($today, $now) {
                        $qq->whereDate('date', $today)->where('hour', '>=', $now);
                    });
            })
            ->count();
    }


    private function assertUnderActiveLimit(User $user): void
    {
        $count = $this->activeEventsCountFor($user);
        if ($count >= self::MAX_ACTIVE_EVENTS) {
            throw ValidationException::withMessages([
                // Si ta vue n’affiche pas errors.limit, mets "name_event" à la place
                'limit' => "Tu as atteint la limite de " . self::MAX_ACTIVE_EVENTS . " événements actifs ou en attente de validation. Désactive/annule un événement avant d’en créer un nouveau.",
            ]);
        }
    }

    /*###############################################################################################*/


    /**=========================================================================
     * >> Affiche la liste des événements actifs & non inactifs | >> Page events
     * ##[Vue : Events/Index]##
     * =========================================================================*/
    public function index(Request $request)
    {
        $selectedCity = $request->input('ville');
        $selectedDate = $request->input('date');
        $selectedPeriodFilter = $request->input('filter'); // null | "week" | "month"
        $selectedInterestId = $request->input('interet');

        $now = now();
        $todayDateString = $now->toDateString();
        $currentTimeString = $now->format('H:i:s');

        // Requête événements actifs
        $eventsQuery = Event::query()
            ->where('inactif', false)
            ->withCount('participants'); // participants_count pour l’affichage

        // >>> Filtre par ville
        if ($selectedCity) {
            $eventsQuery->where('location', $selectedCity);
        }

        // >>> Filtre par date || période
        if (!empty($selectedDate)) {
            $eventsQuery->whereDate('date', $selectedDate);
        } else {
            if ($selectedPeriodFilter === 'week') {
                $eventsQuery->whereBetween('date', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()]);
            } elseif ($selectedPeriodFilter === 'month') {
                $eventsQuery->whereBetween('date', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()]);
            }
        }

        // >>> Filtre par centre d’intérêt
        if (!empty($selectedInterestId)) {
            $eventsQuery->whereHas('centresInteret', function ($relationQuery) use ($selectedInterestId) {
                $relationQuery->where('centres_interet.id', $selectedInterestId);
            });
        }

        // Requête des événements à venir
        $upcomingEventsQuery = (clone $eventsQuery)
            ->where(function ($dateTimeQuery) use ($todayDateString, $currentTimeString) {
                $dateTimeQuery->whereDate('date', '>', $todayDateString)
                    ->orWhere(function ($sameDayQuery) use ($todayDateString, $currentTimeString) {
                        $sameDayQuery->whereDate('date', $todayDateString)
                            ->where('hour', '>=', $currentTimeString);
                    });
            })
            ->orderBy('date', 'asc')
            ->orderBy('hour', 'asc');

        // Requête des événements passés
        $pastEventsQuery = (clone $eventsQuery)
            ->where(function ($dateTimeQuery) use ($todayDateString, $currentTimeString) {
                $dateTimeQuery->whereDate('date', '<', $todayDateString)
                    ->orWhere(function ($sameDayQuery) use ($todayDateString, $currentTimeString) {
                        $sameDayQuery->whereDate('date', $todayDateString)
                            ->where('hour', '<', $currentTimeString);
                    });
            })
            ->orderBy('date', 'desc')
            ->orderBy('hour', 'desc');

        // Pagination (tout en gardant les filtres dans l’URL)
        $upcomingEvents = $upcomingEventsQuery->paginate(8)->withQueryString();
        $pastEvents = $pastEventsQuery->paginate(8)->withQueryString();

        // Liste des centres d’intérêt pour les filtres de la vue
        $interests = CentreInteret::orderBy('name')->get(['id', 'name']);

        // Renvoyer les données à la vue Inertia
        return Inertia::render('Events/Index', [
            'upcomingEvents' => $upcomingEvents,
            'pastEvents' => $pastEvents,
            'interets' => $interests,
            'filters' => [
                'ville' => $selectedCity ?? '',
                'date' => $selectedDate ?? '',
                'filter' => $selectedPeriodFilter ?? '',
                'interet' => $selectedInterestId ?? '',
            ],
        ]);
    }

    /**=========================================================
     * >> Affiche le formulaire de création d'un nouvel événement.
     * ##[Vue : Events/Create]##
     * =========================================================*/
    public function create()
    {
        $interets = CentreInteret::orderBy('name')->get(['id', 'name']);
        return Inertia::render('Events/Create', [
            'interets' => $interets,
        ]);
    }

    /**=================================================
     * >> Enregistre un nouvel événement après validation
     * ==================================================*/
    public function store(Request $request)
    {


        $this->assertUnderActiveLimit($request->user());

        // 1) Valider la requête (messages personnalisés à la fin)
        $validatedData = $request->validate([
            //Requests
            'name_event' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date' => [
                'required',
                'date',
                'after_or_equal:today',
                function ($attribute, $value, $fail) {
                    $selectedDate = Carbon::parse($value);
                    $maxDate = now()->addYear(); // pas plus d’un an dans le futur
                    if ($selectedDate->gt($maxDate)) {
                        $fail('La date ne peut pas être plus d\'un an dans le futur.');
                    }
                },
            ],
            'hour' => 'required|date_format:H:i',
            'location' => 'required|string|max:255',
            'min_person' => 'required|integer|min:1',
            'max_person' => 'required|integer|min:1|gt:min_person',
            'picture_event' => 'nullable|image|mimes:jpeg,png,jpg|max:2048', // Image optionnelle (<= 2 Mo)
            'centres_interet' => 'nullable|array|max:5',
            'centres_interet.*' => 'integer|exists:centres_interet,id',
        ],
            [//Messages
                'name_event.required' => 'Le nom de l\'événement est requis.',
                'date.required' => 'La date est requise.',
                'date.after_or_equal' => 'La date doit être aujourd\'hui ou une date ultérieure.',
                'hour.required' => 'L\'heure est requise.',
                'location.required' => 'Le lieu est requis.',
                'min_person.required' => 'Le nombre minimum de participants est requis.',
                'min_person.min' => 'Le nombre minimum de participants doit être d\'au moins 1.',
                'max_person.required' => 'Le nombre maximum de participants est requis.',
                'max_person.min' => 'Le nombre maximum de participants doit être d\'au moins 1.',
                'max_person.gt' => 'Le nombre maximum de participants doit être supérieur au minimum.',
                'picture_event.image' => 'Le fichier doit être une image.',
                'picture_event.mimes' => 'L\'image doit être de type :jpeg, png, jpg.',
                'picture_event.max' => 'L\'image ne doit pas dépasser 2 Mo.',
            ]);

        // 2) Normaliser l’heure en "HH:MM:SS"
        $validatedData['hour'] = Carbon::createFromFormat('H:i', $validatedData['hour'])
            ->format('H:i:s');

        // 3) Gérer l’image si fournie ; sinon, on stocke null (image par défaut côté vue)
        if ($request->hasFile('picture_event')) {
            // stocke dans storage/app/public/events et renvoie un chemin relatif "events/xxx.jpg"
            $validatedData['picture_event'] = $request->file('picture_event')->store('events', 'public');
        } else {
            $validatedData['picture_event'] = null;
        }

        // 4)  Créateur
        $validatedData['created_by'] = auth()->id();

        // 5) Créer l’événement
        $event = Event::create($validatedData);

        // 6) Lier les centres d’intérêt (table pivot) si fournis
        $event->centresInteret()->sync($request->input('centres_interet', []));

        // 7) Rediriger vers la liste avec un message de succès
        return redirect()
            ->route('events.index')
            ->with('flash', ['success' => 'Événement créé !']);
    }

    /**======================================================
     * >> Affiche le formulaire de modification d'un événement.
     * ##[Vue : Events/Edit]##
     * =======================================================*/
    public function edit(Event $event)
    {
        // 1) Interdire la modification si l'événement est déjà passé
        if ($this->eventIsPast($event)) {
            return back()->with('flash', ['error' => "Modification interdite : événement passé."]);
        }

        // Seul le créateur peut modifier
        if (auth()->id() !== $event->created_by) {
            return back()->with('flash', ['error' => 'Action non autorisée.']);
        }

        $interestOptions = CentreInteret::orderBy('name')->get(['id', 'name']);
        $event->load('centresInteret:id,name');

        return Inertia::render('Events/Edit', [
            'event' => $event,
            'interets' => $interestOptions,
        ]);
    }

    /**========================================================================
     * >> Reçoit le formulaire, valide, met à jour l'événement,
     * gère l'upload/suppression d'image et la sync des centres d'intérêt.
     * ========================================================================*/
    public function update(Request $request, Event $event)
    {
        $user = auth()->user();
        if (!$user) return redirect()->route('login');
        if ($user->id !== $event->created_by) {
            return back()->with('flash', ['error' => 'Action non autorisée.']);
        }

        if ($this->eventIsPast($event)) {
            return $this->backWithFlash("Modification interdite : événement passé.");
        }
        if ($event->cancelled_at) {
            return $this->backWithFlash("Modification interdite : événement annulé.");
        }

        try {
            $rules = [
                'name_event' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'date' => [
                    'bail', 'required', 'date', 'after_or_equal:today',
                    function ($attribute, $value, $fail): void {
                        $d = \Carbon\Carbon::make($value);
                        if (!$d) {
                            $fail('La date est invalide.');
                            return;
                        }
                        if ($d->gt(now()->addYear())) $fail('La date ne peut pas être plus d’un an dans le futur.');
                    },
                ],
                'hour' => ['required', 'date_format:H:i'],
                'location' => ['required', 'string', 'max:255'],
                'min_person' => ['required', 'integer', 'min:1'],
                'max_person' => ['required', 'integer', 'min:1', 'gt:min_person'],
                'picture_event' => ['nullable'],
                'centres_interet' => ['nullable', 'array', 'max:5'],
                'centres_interet.*' => ['integer', 'exists:centres_interet,id'],
            ];
            if ($request->hasFile('picture_event')) {
                $rules['picture_event'] = ['image', 'mimes:jpeg,png,jpg', 'max:2048'];
            }

            $validated = $request->validate($rules, [
                'name_event.required' => "Le nom de l'événement est requis.",
                'date.required' => 'La date est requise.',
                'date.after_or_equal' => "La date doit être aujourd'hui ou ultérieure.",
                'hour.required' => "L'heure est requise.",
                'hour.date_format' => "L'heure doit être au format HH:mm.",
                'location.required' => 'Le lieu est requis.',
                'min_person.required' => 'Le minimum de participants est requis.',
                'min_person.min' => 'Le minimum doit être au moins 1.',
                'max_person.required' => 'Le maximum de participants est requis.',
                'max_person.gt' => 'Le maximum doit être supérieur au minimum.',
                'picture_event.image' => 'Le fichier doit être une image.',
                'picture_event.mimes' => 'Formats autorisés : jpeg, png, jpg.',
                'picture_event.max' => "L'image ne doit pas dépasser 2 Mo.",
            ]);

            if (isset($validated['hour'])) {
                $validated['hour'] = \Carbon\Carbon::createFromFormat('H:i', $validated['hour'])->format('H:i:s');
            }

            $oldImage = $event->picture_event;
            if ($request->input('picture_event') === 'REMOVE_IMAGE') {
                $validated['picture_event'] = null;
                if ($oldImage) \Storage::disk('public')->delete($oldImage);
            } elseif ($request->hasFile('picture_event')) {
                $validated['picture_event'] = $request->file('picture_event')->store('events', 'public');
                if ($oldImage) \Storage::disk('public')->delete($oldImage);
            } else {
                unset($validated['picture_event']);
            }

            $watched = ['name_event', 'date', 'hour', 'location', 'description', 'min_person', 'max_person', 'picture_event'];
            $original = $event->only($watched);
            if (isset($original['hour']) && strlen((string)$original['hour']) === 5) $original['hour'] .= ':00';
            if ($original['date'] instanceof \Carbon\Carbon) $original['date'] = $original['date']->format('Y-m-d');

            // ⚠️ sortir la pivot du validated
            $ciIds = $request->input('centres_interet', []);
            unset($validated['centres_interet']);

            // ⚠️ FIX: préciser la table dans les pluck pour éviter l’ambiguïté
            $oldTags = $event->centresInteret()
                ->pluck('centres_interet.id')
                ->sort()->values()->all();

            $changes = [];

            \DB::transaction(function () use ($event, $validated, $watched, $original, $oldTags, $ciIds, &$changes) {

                $toFill = array_intersect_key($validated, array_flip($event->getFillable()));
                $event->fill($toFill);

                $dirty = $event->getDirty();
                $event->save();

                foreach ($dirty as $field => $newValue) {
                    if (in_array($field, $watched, true)) {
                        if ($field === 'date' && $newValue instanceof \Carbon\Carbon) $newValue = $newValue->format('Y-m-d');
                        if ($field === 'hour' && strlen((string)$newValue) === 5) $newValue .= ':00';
                        $changes[$field] = [
                            'old' => (string)($original[$field] ?? ''),
                            'new' => (string)$newValue,
                        ];
                    }
                }

                $event->centresInteret()->sync($ciIds);

                // ⚠️ FIX: idem ici
                $newTags = $event->centresInteret()
                    ->pluck('centres_interet.id')
                    ->sort()->values()->all();

                if ($oldTags !== $newTags) {
                    $oldNames = CentreInteret::whereIn('id', $oldTags)->pluck('name')->sort()->values()->all();
                    $newNames = CentreInteret::whereIn('id', $newTags)->pluck('name')->sort()->values()->all();
                    $changes['centres_interet'] = [
                        'old' => implode(', ', $oldNames),
                        'new' => implode(', ', $newNames),
                    ];
                }
            });

            if (!empty($changes)) {
                $this->notifyParticipantsInline($event, 'updated', $changes);
                return redirect()->route('events.show', $event->id)
                    ->with('flash', ['success' => "L'événement a été mis à jour avec succès."]);
            }

            return redirect()->route('events.show', $event->id)
                ->with('flash', ['info' => "Aucune modification détectée."]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Throwable $e) {
            \Log::error('event.update failed', [
                'event_id' => $event->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);
            return back()->with('flash', ['error' => "Une erreur est survenue lors de la mise à jour."]);
        }
    }

    /**=========================================================
     * >> Faire rejoindre l'événement à l'utilisateur connecté.
     * ==========================================================*/
    public function join(Event $event)
    {
        // 1) L'utilisateur doit être connecté
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $userId = auth()->id();

        // 2) Garde-fous côté événement
        if ($this->eventIsPast($event)) {
            return back()->with('flash', ['error' => "Action impossible : l'événement est déjà passé."]);
        }
        if ($event->inactif) {
            return back()->with('flash', ['error' => "Action impossible : l'événement est inactif."]);
        }
        if ($event->cancelled_at) {
            return back()->with('flash', ['error' => "Action impossible : l'événement a été annulé."]);
        }

        // 3) Déjà participant ?
        $alreadyIn = $event->participants()->where('user_id', $userId)->exists();
        if ($alreadyIn) {
            return back()->with('flash', ['error' => 'Tu participes déjà à cet événement.']);
        }

        // 4) Capacité atteinte ?
        $currentCount = $event->participants()->count();
        if ($currentCount >= (int)$event->max_person) {
            return back()->with('flash', ['error' => 'Désolé, le maximum de particpant est atteint.']);
        }

        // 5) Ajout (idempotent) à la pivot
        $event->participants()->syncWithoutDetaching([$userId]);

        return back()->with('flash', ['success' => 'Tu as rejoint cet évènement !']);
    }

    /**====================================================================================
     * >> Basculer la participation de l'utilisateur (participer <-> annuler).
     * ======================================================================================*/
    public function toggleParticipation(Event $event)
    {
        // 1) L'utilisateur doit être connecté
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $userId = auth()->id();

        // 2) AUCUNE action possible si l’événement est passé
        if ($this->eventIsPast($event)) {
            return redirect()
                ->route('events.show', $event->id)
                ->with('flash', ['error' => "Action impossible : l'événement est déjà passé."]);
        }

        // 3) Est-ce que l'utilisateur participe déjà ?
        $isParticipating = $event->participants()->where('user_id', $userId)->exists();

        if ($isParticipating) {
            // 3.a) Annuler la participation
            $event->participants()->detach($userId);
            $message = 'Participation annulée avec succès.';
        } else {
            // 3.b) Ajouter la participation -> mêmes gardes que join()
            if ($event->inactif) {
                return redirect()
                    ->route('events.show', $event->id)
                    ->with('flash', ['error' => "Action impossible : l'événement est inactif."]);
            }
            if ($event->cancelled_at) {
                return redirect()
                    ->route('events.show', $event->id)
                    ->with('flash', ['error' => "Action impossible : l'événement a été annulé."]);
            }

            $currentCount = $event->participants()->count();
            if ($currentCount >= (int)$event->max_person) {
                return redirect()
                    ->route('events.show', $event->id)
                    ->with('flash', ['error' => 'La capacité maximale est atteinte.']);
            }

            $event->participants()->syncWithoutDetaching([$userId]);
            $message = 'Participation enregistrée avec succès.';
        }

        // 4) Rafraîchir la relation (compteur/affichage)
        $event->load('participants');

        return redirect()
            ->route('events.show', $event->id)
            ->with('flash', ['success' => $message]);
    }

    /**==============================================================================
     * >> Affiche la page "détails" d’un événement + la liste des commentaires.
     * ==============================================================================*/
    public function show(Event $event)
    {
        // 0) L’utilisateur doit être connecté
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        // 1) Infos utilisateur courant
        $user      = auth()->user();
        $isCreator = $user->id === $event->created_by; // peut te servir pour d'autres indicateurs (can_edit)
        $isAdmin   = $user->hasAnyRole(['Admin', 'Super-admin']);

            // 2) Si l’événement est inactif => **seul** un admin peut voir la page.
        if ($event->inactif && !$isAdmin) {
            return redirect()->route('events.index')
                ->with('flash', ['error' => "Cet événement n'est plus disponible."]);
        }

        // 3) Charger les relations nécessaires pour l’affichage
        $event->load([
            'creator:id,pseudo',
            'participants:id,pseudo,picture_profil',
            'centresInteret:id,name',
        ]);

        // 4) Récupérer les commentaires (avec l’auteur de chaque message)
        $messages = Message::query()
            ->where('event_id', $event->id)
            ->with('user:id,pseudo,picture_profil')
            ->latest()
            ->get();



        // 5) Renvoyer la page Inertia avec toutes les données utiles
        return Inertia::render('Events/Show', [
            'event' => array_merge($event->toArray(), [
                'creator' => $event->creator,
                'participants' => $event->participants,
                'created_by' => $event->created_by,

                // Seul le créateur peut modifier (pas l’admin, sauf s’il est aussi créateur)
                'can_edit' => $isCreator,

                // Indicateur pratique pour bloquer des actions côté front
                'is_past' => $this->eventIsPast($event),
            ]),
            'messages' => $messages,
            'already_reported' => session()->has("reported_event_{$event->id}"),
        ]);
    }

    /**====================================================
     * >> Désactive (met "inactif" = true) un événement.
     * ====================================================*/
    public function deactivate(Request $request, Event $event): RedirectResponse|JsonResponse
    {
        // 0) Vérifier l'authentification : sinon -> login
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        // 1) Vérifier les permissions (créateur OU admin/super-admin)
        $user = auth()->user();
        $isCreator = ($user->id === $event->created_by);
        $isAdmin = $user->hasAnyRole(['Admin', 'Super-admin']);

        if (!($isCreator || $isAdmin)) {
            return back()->with('flash', ['error' => 'Action non autorisée.']);
        }

        // 2) Si déjà inactif, on évite un write inutile et on informe l'utilisateur
        if ($event->inactif) {
            return back()->with('flash', ['info' => "L'événement est déjà désactivé."]);
        }

        //Notif mail
        $this->notifyParticipantsInline($event, 'deactivated');

        // 3) Mettre l’événement en inactif
        $event->update(['inactif' => true]);

        // 4) Réponse de succès :
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "L'événement a été désactivé. Il n'est plus visible des autres utilisateurs.",
            ]);
        }

        return redirect()
            ->route('events.index')
            ->with('flash', ['success' => "L'événement a été désactivé. Il n'est plus visible des autres utilisateurs."]);
    }

    /**===================================================================
     * >>Supprime "logiquement" un événement (ici, on délègue à deactivate).
     * =====================================================================*/
    public function destroy(Request $request, Event $event)
    {
        // On réutilise exactement la même logique que deactivate()
        return $this->deactivate($request, $event);
    }

    /**=================================================================
     * >> Active / Désactive un événement (toggle du champ 'inactif').
     * ==================================================================*/
    public function toggleActive(Request $request, Event $event)
    {
        // 0) Auth : si pas connecté -> page de login
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        // 1) Droits : Admin / Super-admin uniquement
        $user = auth()->user();
        $isAdmin = $user->hasAnyRole(['Admin', 'Super-admin']);
        if (!$isAdmin) {
            return back()->with('flash', ['error' => 'Action non autorisée.']);
        }

        // 2) Règles
        // 2.a) Événement passé -> on ne permet pas le toggle
        if ($this->eventIsPast($event)) {
            return redirect()
                ->route('events.show', $event->id)
                ->with('flash', ['error' => "Action impossible : l'événement est déjà passé."]);
        }

        // 2.b) Si l’événement a été annulé par le créateur -> on empêche la réactivation
        if ($event->cancelled_at && $event->inactif === true) {
            return back()->with('flash', ['error' => "Action impossible : l'événement a été annulé par le créateur."]);
        }

        // 3) Basculer l'état "inactif"
        $event->update(['inactif' => !$event->inactif]);

        // 4) Réponse de succès
        $message = $event->inactif
            ? 'Événement désactivé.'
            : 'Événement activé.';

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => ['inactif' => $event->inactif],
            ]);
        }

        return back()->with('flash', ['success' => $message]);
    }

    /**============================================================
     * >> Valide (accepte) un événement : le rend confirmé et actif.
     * ==============================================================*/
    public function accept(Event $event)
    {
        // 0) Auth
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        // 1) Droits
        $user = auth()->user();
        if (!$user->hasAnyRole(['Admin', 'Super-admin'])) {
            return back()->with('flash', ['error' => "Action non autorisée."]);
        }

        // 2) Garde-fous métier
        if ($event->cancelled_at) {
            return back()->with('flash', ['error' => "Impossible d'accepter : l'événement a été annulé par le créateur."]);
        }

        if ($this->eventIsPast($event)) {
            return back()->with('flash', ['error' => "Impossible d'accepter : l'événement est déjà passé."]);
        }

        // Déjà accepté ?
        if ($event->confirmed && !$event->inactif) {
            return back()->with('flash', ['info' => "L'événement est déjà accepté et actif."]);
        }

        // 3) Mise à jour
        $event->update([
            'confirmed' => true,
            'inactif' => false,
            'validated_by_id' => $user->id,
            'validated_at' => now(),
        ]);
        // 4) Ajouter le créateur comme participant (si pas déjà)
        $event->participants()->syncWithoutDetaching([$event->created_by]);

        // 5) Succès
        return back()->with('flash', ['success' => 'Événement accepté.']);
    }

    /**=========================================================
     * >> Refuse un événement : le rend non confirmé et inactif.
     * ============================================================
     */
    public function refuse(Event $event)
    {
        // 0) Auth
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        // 1) Droits
        $user = auth()->user();
        if (!$user->hasAnyRole(['Admin', 'Super-admin'])) {
            return back()->with('flash', ['error' => "Action non autorisée."]);
        }

        // 2) Si déjà annulé, le refuser ne change rien de plus : informer simplement
        if ($event->cancelled_at) {
            return back()->with('flash', ['info' => "L'événement a déjà été annulé par le créateur."]);
        }

        // Déjà refusé/inactif ?
        if (!$event->confirmed && $event->inactif) {
            return back()->with('flash', ['info' => "L'événement est déjà refusé (inactif)."]);
        }

        // 3) Mise à jour
        $event->update([
            'confirmed' => false,
            'inactif' => true,
            'validated_by_id' => $user->id,
            'validated_at' => now(),
        ]);

        // 4) Succès
        return back()->with('flash', ['success' => 'Événement refusé.']);
    }

    /**===========================================
     * >> Annule un événement (créateur uniquement)
     * ==============================================
     * */
    public function cancel(Event $event)
    {
        // 0) Authentification : si l’utilisateur n’est pas connecté, on l’envoie au login
        if (!auth()->check()) {
            return redirect()->route('login');
        }
        // 1) Autorisation : SEUL le créateur peut annuler
        $currentUserId = auth()->id();
        if ($currentUserId !== $event->created_by) {
            return back()->with('flash', ['error' => 'Seul le créateur peut annuler cet événement.']);
        }
        // 2) Règle métier : on n’annule pas un événement déjà passé
        if ($this->eventIsPast($event)) {
            return back()->with('flash', ['error' => "Action impossible : l'événement est déjà passé."]);
        }
        // 3) Idempotence : si déjà annulé, on ne fait rien
        if ($event->cancelled_at) {
            return back()->with('flash', ['info' => 'Cet événement est déjà annulé.']);
        }
        // 4) Mise à jour : on désactive et on pose les méta-données d’annulation
        $event->update([
            'inactif' => true,
            'cancel_note' => 'Événement annulé le ' . now()->format('d/m/Y à H:i'),
            'cancelled_at' => now(),
            'cancelled_by' => $currentUserId,
        ]);

        //Notif par mail
        $this->notifyParticipantsInline($event, 'cancelled');

        // 5) Retour avec confirmation
        return back()->with('flash', ['success' => "L'événement a été annulé (action définitive)."]);
    }

    /**============================================
     * >> Signale un événement (bouton "Signaler").
     * ==============================================*/
    public function report(Event $event)
    {
        // 0) Auth : si l'utilisateur n'est pas connecté → login
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        // 1) Clé de session pour mémoriser "déjà signalé"
        //    On ajoute l'ID user pour éviter les collisions si la même session
        //    est réutilisée par plusieurs comptes sur le même navigateur.
        $sessionKey = "reported_event_{$event->id}_user_" . auth()->id();

        // 2) Si déjà signalé dans CETTE session → on ne refait rien
        if (session()->has($sessionKey)) {
            return back()->with('flash', ['info' => 'Vous avez déjà signalé cet événement. Merci !']);
        }

        try {
            // 3) Incrémente le compteur en base (+1)
            $event->increment('reports_count');

            // 4) Marque la session pour empêcher un second clic
            session()->put($sessionKey, true);

            // 5) Retour avec confirmation
            return back()->with('flash', ['success' => 'Merci pour votre signalement.']);
        } catch (\Throwable $e) {
            // Log technique + message utilisateur propre
            logger()->error("Erreur lors du signalement de l'événement {$event->id}", [
                'user_id' => auth()->id(),
                'message' => $e->getMessage(),
            ]);

            return back()->with('flash', [
                'error' => "Impossible d'enregistrer votre signalement pour le moment."
            ]);
        }
    }

    /**=============================================================
     * >> Remet à zéro le compteur de signalements d’un événement.
     * =============================================================*/
    public function clearReports(Event $event)
    {
        // 0) Auth
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        // 1) Droits
        if (!auth()->user()->hasAnyRole(['Admin', 'Super-admin'])) {
            return back()->with('flash', ['error' => 'Action non autorisée.']);
        }

        try {
            // 2) Reset du compteur
            $event->update(['reports_count' => 0]);

            // Note : on ne peut pas “vider” les sessions des autres utilisateurs.
            // Ils verront encore "déjà signalé" jusqu’à fin de leur session.
            return back()->with('flash', ['success' => 'Signalements remis à zéro.']);
        } catch (\Throwable $e) {
            logger()->error("clearReports failed for event {$event->id}", [
                'user_id' => auth()->id(),
                'message' => $e->getMessage(),
            ]);
            return back()->with('flash', ['error' => "Impossible de remettre les signalements à zéro."]);
        }
    }

    /**======================================================
     * >> Exporte la liste des événements en CSV (UTF-8 + BOM).
     * =======================================================*/
    public function exportEvents()
    {
        // 0) Auth
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        // 1) Droits : Admin / Super-admin uniquement
        if (!auth()->user()->hasAnyRole(['Admin', 'Super-admin'])) {
            return back()->with('flash', ['error' => "Action non autorisée."]);
        }

        // 2) Nom de fichier (ex: evenements_2025-09-14_10-42-00.csv)
        $filename = 'parentswood_evenements_' . now()->format('Y-m-d_H-i-s') . '.csv';


        // 3) Stream du CSV
        return response()->streamDownload(function () {
            // Ouvre un "flux" lié à la réponse HTTP
            $handle = fopen('php://output', 'w');

            // (Important pour Excel) Ajoute le BOM UTF-8
            fwrite($handle, "\xEF\xBB\xBF");

            // Écrit l'en-tête | délimiteur ';' (3e param de fputcsv) pour Excel FR
            fputcsv($handle, ['ID', "Nom de l'événement", 'Date', 'Heure', 'Lieu'], ';');


            // Récupère les events par paquets
            Event::query()
                ->select('id', 'name_event', 'date', 'hour', 'location')
                ->orderBy('id')
                ->chunkById(1000, function ($events) use ($handle) {
                    foreach ($events as $event) {
                        // Normalise date
                        $dateStr = $event->date instanceof Carbon
                            ? $event->date->format('Y-m-d')
                            : (string)$event->date;

                        // Normalise heure → "HH:mm"
                        $hourRaw = (string)($event->hour ?? '');
                        if ($hourRaw === '') {
                            $hourStr = '';
                        } elseif (strlen($hourRaw) === 5) { // "HH:mm"
                            $hourStr = $hourRaw;
                        } else {
                            // "HH:mm:ss" ou autre → on reformate
                            try {
                                $hourStr = Carbon::parse($hourRaw)->format('H:i');
                            } catch (\Exception $e) {
                                $hourStr = $hourRaw; // fallback brut si parsing impossible
                            }
                        }

                        // Écrit la ligne
                        fputcsv($handle, [
                            $event->id,
                            $event->name_event,
                            $dateStr,
                            $hourStr,
                            $event->location,
                        ], ';');
                    }
                });

            fclose($handle);
        }, $filename, [
            // 4) Headers HTTP
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }


}

