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


class EventController extends Controller
{
    /**
     * Affiche la liste des événements actifs (non inactifs), paginés par 10 || Page events , no admin
     * Vue : Events/Index
     */
    public function index(Request $request)
    {
        $ville = $request->input('ville');
        $date = $request->input('date');
        $filter = $request->input('filter');
        $interetId = $request->input('interet');

        $today   = now()->toDateString();
        $nowTime = now()->format('H:i:s');

        $query = Event::where('inactif', false);

        if ($ville) {
            $query->where('location', $ville);
        }

        if ($date) {
            $query->whereDate('date', $date);
        }

        if ($filter === 'week') {
            $query->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($filter === 'month') {
            $query->whereBetween('date', [now()->startOfMonth(), now()->endOfMonth()]);
        }

        if ($interetId) {
            $query->whereHas('centresInteret', fn ($q) => $q->where('centres_interet.id', $interetId));
        }

        // Compteurs de participants attendus par la vue
        $query->withCount('participants')
            ->orderBy('date', 'asc')
            ->orderBy('hour', 'asc');

        $upcomingEvents = (clone $query)
            ->where(function ($q) use ($today, $nowTime) {
                $q->whereDate('date', '>', $today)
                    ->orWhere(function ($q) use ($today, $nowTime) {
                        $q->whereDate('date', $today)
                            ->where('hour', '>=', $nowTime);
                    });
            })
            ->paginate(10)
            ->withQueryString();

        $pastEvents = Event::where('inactif', false)
            ->where(function ($q) use ($today, $nowTime) {
                $q->whereDate('date', '<', $today)
                    ->orWhere(function ($q) use ($today, $nowTime) {
                        $q->whereDate('date', $today)
                            ->where('hour', '<', $nowTime);
                    });
            })
            ->withCount('participants')
            ->orderBy('date', 'desc')
            ->orderBy('hour', 'desc')
            ->paginate(10)
            ->withQueryString();

        $interets = CentreInteret::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Events/Index', [
            'upcomingEvents' => $upcomingEvents,
            'pastEvents'     => $pastEvents,
            'interets'       => $interets,
            'filters'        => [
                'ville'   => $ville ?? '',
                'date'    => $date ?? '',
                'filter'  => $filter ?? '',
                'interet' => $interetId ?? '',
                ]
        ]);
    }


    /**
     * Affiche le formulaire de création d'un nouvel événement.
     * Vue : Events/Create
     */
    public function create()
    {
        $interets = \App\Models\CentreInteret::orderBy('name')->get(['id','name']);

        return Inertia::render('Events/Create', [
            'interets' => $interets,
        ]);
    }


    /**
     * Enregistre un nouvel événement après validation.
     * Gère aussi l'upload de l'image si présente.
     * Redirige vers la liste des événements avec un message de succès.
     */
    public function store(Request $request)
    {
        // Validation des champs avec messages personnalisés
        $validated = $request->validate([
            'name_event' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date' => [
                'required',
                'date',
                'after_or_equal:today', function ($attribute, $value, $fail) {
                    $selectedDate = \Carbon\Carbon::parse($value);
                    $maxDate = now()->addYear();
                    if ($selectedDate->gt($maxDate)) {
                        $fail('La date ne peut pas être plus d\'un an dans le futur.');
                    }
                }],
            //Requests
            'hour' => 'required',
            'location' => 'required|string|max:255',
            'min_person' => 'required|integer|min:1',
            'max_person' => 'required|integer|min:1|gt:min_person',
            'picture_event' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',

            'centres_interet'   => 'nullable|array|max:5',
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

        // Gestion de l'image uploadée
        if ($request->hasFile('picture_event')) {
            $validated['picture_event'] = $request->file('picture_event')->store('events', 'public');
        } else {// Image par défaut
            $validated['picture_event'] = null;
        }

        $validated['created_by'] = auth()->id();

        $event = Event::create($validated);

        $event->centresInteret()->sync($request->input('centres_interet', []));
        return redirect()->route('events.index')->with('success', 'Événement créé !');
    }

    /**
     * Affiche le formulaire de modification d'un événement.
     * Vue : Events/Edit
     */
    public function edit(Event $event)
    {
        // Vérifie que seul le créateur ou un admin/super-admin peut modifier
        if (auth()->id() !== $event->created_by && !auth()->user()->hasAnyRole(['Admin', 'Super-admin'])) {
            abort(403, 'Action non autorisée.');
        }

        $interets = CentreInteret::orderBy('name')->get(['id','name']);

        // ⬅️ Important : on charge la relation sur l’event
        $event->load('centresInteret:id,name');

        return Inertia::render('Events/Edit', [
            'event'    => $event,
            'interets' => $interets,

        ]);
    }

    /**
     * reçoit le formulaire rempli, valide, met à jour l’event, gère l’upload/suppression d’image,
     * fait le sync() des centres d’intérêt, puis renvoie une réponse (JSON/redirect).*
     */
    public function update(Request $request, Event $event)
    {
        // Autorisation
        if (auth()->id() !== $event->created_by && !auth()->user()->hasAnyRole(['Admin', 'Super-admin'])) {
            return response()->json(['message' => 'Action non autorisée.'], 403);
        }

        try {
            // Règles de base
            $rules = [
                'name_event'  => 'required|string|max:255',
                'description' => 'nullable|string',
                'date'        => [
                    'required','date','after_or_equal:today',
                    function ($attribute, $value, $fail) {
                        $selectedDate = \Carbon\Carbon::parse($value);
                        $maxDate = now()->addYear();
                        if ($selectedDate->gt($maxDate)) {
                            $fail('La date ne peut pas être plus d\'un an dans le futur.');
                        }
                    },
                ],
                'hour'        => 'required',
                'location'    => 'required|string|max:255',
                'min_person'  => 'required|integer|min:1',
                'max_person'  => 'required|integer|min:1|gt:min_person',
                'picture_event' => ['nullable'],

                'centres_interet'   => 'nullable|array|max:5',
                'centres_interet.*' => 'integer|exists:centres_interet,id',

            ];

            if ($request->hasFile('picture_event')) {
                $rules['picture_event'] = ['image','mimes:jpeg,png,jpg','max:2048'];
            }

            $messages = [
                'name_event.required' => 'Le nom de l\'événement est requis.',
                'date.required'       => 'La date est requise.',
                'date.after_or_equal' => 'La date doit être aujourd\'hui ou une date ultérieure.',
                'hour.required'       => 'L\'heure est requise.',
                'location.required'   => 'Le lieu est requis.',
                'min_person.required' => 'Le nombre minimum de participants est requis.',
                'min_person.min'      => 'Le nombre minimum de participants doit être d\'au moins 1.',
                'max_person.required' => 'Le nombre maximum de participants est requis.',
                'max_person.min'      => 'Le nombre maximum de participants est de 1 au minimum.',
                'max_person.gt'       => 'Le nombre maximum de participants doit être supérieur au minimum.',
                'picture_event.image' => 'Le fichier doit être une image.',
                'picture_event.mimes' => 'L\'image doit être de type :jpeg, png, jpg.',
                'picture_event.max'   => 'L\'image ne doit pas dépasser 2 Mo.',
            ];

            $validated = $request->validate($rules, $messages);

            // Gestion de l'image
            if ($request->input('picture_event') === 'REMOVE_IMAGE') {
                // Supprimer l’ancienne si présente
                if ($event->picture_event) {
                    Storage::delete('public/'.$event->picture_event);
                }
                $validated['picture_event'] = null;
            } elseif ($request->hasFile('picture_event')) {
                // Remplacement par une nouvelle
                if ($event->picture_event) {
                    Storage::delete('public/'.$event->picture_event);
                }
                $validated['picture_event'] = $request->file('picture_event')->store('events', 'public');
            } else {
                // Ne pas toucher au champ si rien n'a changé
                unset($validated['picture_event']);
            }

            $event->update($validated);
            $event->centresInteret()->sync($request->input('centres_interet', []));


            return response()->json([
                'success' => true,
                'message' => 'L\'événement a été mis à jour avec succès.'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors'  => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la mise à jour de l\'événement: '.$e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la mise à jour de l\'événement.',
            ], 500);
        }
    }


    /**
     * Permet à un utilisateur de rejoindre un événement.
     * Crée une relation dans la table pivot participants.
     */
    public function join(Event $event)
    {
        if ($event->participants()->where('user_id', auth()->id())->exists()) {
            return back()->with('error', 'Tu participes déjà à cet événement.');
        }

        $event->participants()->attach(auth()->id());
        return back()->with('success', 'Tu as rejoint cet évènement !');
    }


    public function toggleParticipation(Event $event)
    {
        $user = auth()->user();

        // Vérifier si l'utilisateur participe déjà à l'événement
        $isParticipating = $event->participants()->where('user_id', $user->id)->exists();

        if ($isParticipating) {
            // Retirer l'utilisateur des participants
            $event->participants()->detach($user->id);
            $message = 'Participation annulée avec succès.';
        } else {
            // Ajouter l'utilisateur aux participants
            $event->participants()->attach($user->id);
            $message = 'Participation enregistrée avec succès.';
        }

        // Recharger les relations pour mettre à jour le compteur
        $event->load('participants');

        return back()->with([
            'status' => $message,
            'event' => $event->fresh()
        ]);
    }


    /**
     * Affiche les détails d’un événement et les messages/commentaires associés.
     */
    public function show(Event $event)
    {
        if (!auth()->check()) return redirect()->route('login');

        if ($event->inactif && !auth()->user()->hasAnyRole(['Admin', 'Super-admin']) && auth()->id() !== $event->created_by) {
            return redirect()->route('events.index')->with('error', 'Cet événement n\'est plus disponible.');
        }

        $event->load([
            'creator:id,pseudo',
            'participants:id,pseudo',
            'centresInteret:id,name',
        ]);

        $messages = Message::where('event_id', $event->id)
            ->with('user:id,pseudo')
            ->latest()
            ->get();

        return Inertia::render('Events/Show', [
            'event' => [
                ...$event->toArray(),
                'creator'      => $event->creator,
                'participants' => $event->participants,
                'created_by'   => $event->created_by,
                'can_edit'     => auth()->id() === $event->created_by || auth()->user()->hasAnyRole(['Admin', 'Super-admin']),
            ],
            'messages'          => $messages,
            'already_reported'  => session()->has("reported_event_{$event->id}"),
        ]);
    }


    /**
     * Désactive un événement
     *
     */
    public function deactivate(Request $request, Event $event): RedirectResponse|JsonResponse
    {
        // Créateur OU Admin/Super-admin
        if (auth()->id() !== $event->created_by && !auth()->user()->hasAnyRole(['Admin', 'Super-admin'])) {
            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => 'Action non autorisée.'], 403)
                : back()->with('error', 'Action non autorisée.');
        }

        $event->update([
            'inactif' => true,
        ]);

        // Si appel AJAX (axios/fetch) -> JSON. Sinon -> redirection + flash (Inertia).
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "L'événement a été désactivé. Il n'est plus visible des autres utilisateurs."
            ]);
        }

        return redirect()->route('events.index')
            ->with('success', "L'événement a été désactivé. Il n'est plus visible des autres utilisateurs.");
    }



    public function destroy(Request $request, Event $event)
    {
        return $this->deactivate($request, $event);
    }


    /**
     * Active ou désactive un événement (bascule le champ 'inactif').
     * Admin/Super-admin uniquement.
     */
    public function toggleActive(Event $event)
    {
        // Admin / Super-admin uniquement
        if (!auth()->user()->hasAnyRole(['Admin', 'Super-admin'])) {
            return back()->with('error', 'Action non autorisée.');
        }
        // Si l’événement a été annulé par le créateur -> non réactivable
        if ($event->cancelled_at && $event->inactif === true) {
            return back()->with('error', 'Événement annulé par le créateur : réactivation interdite.');
        }
        $event->update(['inactif' => !$event->inactif]);
        return back()->with('success',
            $event->inactif ? 'Événement désactivé.' : 'Événement activé.'
        );
    }


    /**
     * Marque un événement comme confirmé et actif.
     * Utilisé dans une logique de validation manuelle.
     * Admin/Super-admin uniquement.
     */
    public function accept(Event $event)
    {
        if (!auth()->user()->hasAnyRole(['Admin', 'Super-admin'])) {
            return back()->with('flash', ['error' => "Action non autorisée."]);
        }
        $event->update([
            'confirmed' => true,
            'inactif' => false,
            'validated_by_id' => auth()->id(),
            'validated_at' => now(),
        ]);
        return back()->with('success', 'Événement accepté.');
    }

    /**
     * Refuse un événement (le rend inactif et non confirmé).
     * Admin/Super-admin uniquement.
     */
    public function refuse(Event $event)
    {
        if (!auth()->user()->hasAnyRole(['Admin', 'Super-admin'])) {
            return back()->with('flash', ['error' => "Action non autorisée."]);
        }

        $event->update([
            'confirmed' => false,
            'inactif' => true,
            'validated_by_id' => auth()->id(),
            'validated_at' => now(),
        ]);

        return back()->with('success', 'Événement refusé.');
    }

    public function exportEvents()
    {
        $events = Event::select('id', 'name_event', 'date', 'hour', 'location')->get();

        $csv = fopen('php://output', 'w');
        $filename = 'evenements_' . now()->format('Y-m-d_H-i-s') . '.csv';

        return response()->streamDownload(function () use ($events, $csv) {
            fputcsv($csv, ['ID', 'Nom de l\'événement', 'Date', 'Heure', 'Lieu']);
            foreach ($events as $event) {
                fputcsv($csv, [
                    $event->id,
                    $event->name_event,
                    $event->date,
                    $event->hour,
                    $event->location,
                ]);
            }
            fclose($csv);
        }, $filename);
    }


    //Annuler event
    public function cancel(Event $event)
    {
        // Créateur SEULEMENT
        if (auth()->id() !== $event->created_by) {
            return back()->with('error', 'Seul le créateur peut annuler cet événement.');
        }

        // Si déjà annulé, on ne fait rien
        if ($event->cancelled_at) {
            return back()->with('info', 'Cet événement est déjà annulé.');
        }

        $event->update([
            'inactif'      => true,
            'cancel_note'  => 'Événement annulé le ' . now()->format('d/m/Y à H:i'),
            'cancelled_at' => now(),
            'cancelled_by' => auth()->id(), // = créateur
        ]);

        return back()->with('success', "L'événement a été annulé (action définitive).");
    }



// Empêche plusieurs incréments par la même session
    public function report(Event $event)
    {
        // Tous les users sont loggés chez toi, donc ok.
        $key = "reported_event_{$event->id}";
        if (session()->has($key)) {
            return back()->with('success', 'Merci, vous avez déjà signalé cet événement.');
        }

        // Incrément simple et marquage session
        $event->increment('reports_count');
        session()->put($key, true);

        return back()->with('success', 'Merci pour votre signalement.');
    }



// Remise à zéro du compteur (à déclencher côté Admin/Super-admin)
    public function clearReports(Event $event)
    {
        if (!auth()->user()->hasAnyRole(['Admin','Super-admin'])) {
            abort(403);
        }

        $event->update(['reports_count' => 0]);

        // Optionnel : vider les “déjà signalé” de toutes les sessions n’est pas faisable,
        // mais ce n’est pas gênant : au pire l’utilisateur verra “déjà signalé” jusqu’à
        // la fin de sa session.
        return back()->with('success', 'Signalements remis à zéro.');
    }
}

