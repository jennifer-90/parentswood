<?php

namespace App\Http\Controllers;

use App\Models\Event;
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

        $query = Event::where('inactif', false);

        if ($ville) {
            $query->where('location', $ville);
        }

        if ($date) {
            $query->whereDate('date', $date);
        }

        if ($filter === 'week') {
            $startOfWeek = now()->startOfWeek();
            $endOfWeek = now()->endOfWeek();
            $query->whereBetween('date', [$startOfWeek, $endOfWeek]);
        }

        if ($filter === 'month') {
            $startOfMonth = now()->startOfMonth();
            $endOfMonth = now()->endOfMonth();
            $query->whereBetween('date', [$startOfMonth, $endOfMonth]);
        }

        // Tri par date croissante
        $query->orderBy('date', 'asc');

        $upcomingEvents = $query->whereDate('date', '>', now())->paginate(10)->withQueryString();
        $pastEvents = Event::where('inactif', false)
            ->whereDate('date', '<=', now())
            ->orderBy('date', 'desc')
            ->get();

        return Inertia::render('Events/Index', [
            'upcomingEvents' => $upcomingEvents,
            'pastEvents' => $pastEvents,
        ]);
    }



    /**
     * Affiche le formulaire de création d'un nouvel événement.
     * Vue : Events/Create
     */
    public function create()
    {
        return Inertia::render('Events/Create');
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
                'after_or_equal:today',
                function ($attribute, $value, $fail) {
                    $selectedDate = \Carbon\Carbon::parse($value);
                    $maxDate = now()->addYear();
                    if ($selectedDate->gt($maxDate)) {
                        $fail('La date ne peut pas être plus d\'un an dans le futur.');
                    }
                },
            ],
            'hour' => 'required',
            'location' => 'required|string|max:255',
            'min_person' => 'required|integer|min:1',
            'max_person' => 'required|integer|min:1|gt:min_person',
            'picture_event' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ], [
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
        } else {
            // Image par défaut
            $validated['picture_event'] = null;
        }


        // Attribution de l'utilisateur créateur
        $validated['created_by'] = auth()->id();

        // Création de l'événement
        Event::create($validated);

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

        return Inertia::render('Events/Edit', [
            'event' => $event
        ]);
    }


    public function update(Request $request, Event $event)
    {
        // Vérifie que seul le créateur ou un admin peut modifier
        if (auth()->id() !== $event->created_by && !auth()->user()->hasAnyRole(['Admin', 'Super-admin'])) {
            return response()->json(['message' => 'Action non autorisée.'], 403);
        }

        try {
            // Validation des champs avec messages personnalisés
            $validated = $request->validate([
                'name_event' => 'required|string|max:255',
                'description' => 'nullable|string',
                'date' => [
                    'required',
                    'date',
                    'after_or_equal:today',
                    function ($attribute, $value, $fail) {
                        $selectedDate = \Carbon\Carbon::parse($value);
                        $maxDate = now()->addYear();
                        if ($selectedDate->gt($maxDate)) {
                            $fail('La date ne peut pas être plus d\'un an dans le futur.');
                        }
                    },
                ],
                'hour' => 'required',
                'location' => 'required|string|max:255',
                'min_person' => 'required|integer|min:1',
                'max_person' => 'required|integer|min:1|gt:min_person',
                'picture_event' => [
                    'nullable',
                    'sometimes',
                    'image',
                    'mimes:jpeg,png,jpg',
                    'max:2048', // 2MB
                    function ($attribute, $value, $fail) {
                        // Si c'est une chaîne, c'est probablement 'REMOVE_IMAGE'
                        if (is_string($value) && $value === 'REMOVE_IMAGE') {
                            return;
                        }
                        
                        // Si c'est un objet UploadedFile, la validation standard s'appliquera
                        if ($value instanceof \Illuminate\Http\UploadedFile) {
                            return;
                        }
                        
                        // Si on arrive ici, le format n'est pas valide
                        return $fail('Le format du fichier image n\'est pas valide.');
                    }
                ],
            ], [
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
            ]);

            // Gestion de l'image - Même logique que dans la méthode store
            if ($request->has('picture_event') && $request->input('picture_event') === 'REMOVE_IMAGE') {
                // Supprimer l'image existante
                if ($event->image) {
                    Storage::delete('public/' . $event->image);
                }
                $validated['image'] = null;
            } elseif ($request->hasFile('picture_event')) {
                // Supprimer l'ancienne image si elle existe
                if ($event->image) {
                    Storage::delete('public/' . $event->image);
                }
                
                // Stocker la nouvelle image (même méthode que dans store)
                $validated['image'] = $request->file('picture_event')->store('events', 'public');
            } else {
                // Conserver l'image existante si aucune nouvelle image n'est fournie
                $validated['image'] = $event->image;
            }

            // Mise à jour de l'événement
            $event->update($validated);

            // Retourner une réponse de succès simple
            return response()->json([
                'success' => true,
                'message' => 'L\'événement a été mis à jour avec succès.'
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            // En cas d'erreur de validation, retourner les erreurs
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            // En cas d'autre erreur, la logger et retourner un message générique
            \Log::error('Erreur lors de la mise à jour de l\'événement: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la mise à jour de l\'événement: ' . $e->getMessage(),
                'error' => $e->getMessage()
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
     * Vue : Events/Show
     */
    public function show(Event $event)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }
        
        // Vérifier si l'événement est inactif et que l'utilisateur n'est ni admin ni créateur
        if ($event->inactif && !auth()->user()->hasAnyRole(['Admin', 'Super-admin']) && auth()->id() !== $event->created_by) {
            return redirect()->route('events.index')->with('error', 'Cet événement n\'est plus disponible.');
        }
        
        $messages = Message::where('event_id', $event->id)
            ->with('user:id,pseudo')
            ->latest()
            ->get();

        return Inertia::render('Events/Show', [
            'event' => [
                ...$event->toArray(),
                'creator' => $event->creator,
                'participants' => $event->participants,
                'created_by' => $event->created_by,
                'can_edit' => auth()->id() === $event->created_by || auth()->user()->hasAnyRole(['Admin', 'Super-admin']),
            ],
            'messages' => $messages,
        ]);
    }
    
    /**
     * Désactive un événement au lieu de le supprimer
     *
     * @param  \App\Models\Event  $event
     * @return \Illuminate\Http\Response
     */
    public function deactivate(Event $event)
    {
        // Vérifie que seul le créateur ou un admin peut désactiver
        if (auth()->id() !== $event->created_by && !auth()->user()->hasAnyRole(['Admin', 'Super-admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Action non autorisée.'
            ], 403);
        }

        try {
            // Mettre à jour le statut de l'événement comme inactif
            $event->update([
                'inactif' => true,
                'report' => 'Désactivé par l\'organisateur le ' . now()->format('d/m/Y à H:i')
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'L\'événement a été désactivé avec succès et n\'est plus visible par les autres utilisateurs.'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la désactivation de l\'événement: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la désactivation de l\'événement.'
            ], 500);
        }
    }
    
    /**
     * Supprime un événement de la base de données.
     *
     * @param  \App\Models\Event  $event
     * @return \Illuminate\Http\Response
     */
    public function destroy(Event $event)
    {
        // Vérifie que seul le créateur ou un admin peut supprimer
        if (auth()->id() !== $event->created_by && !auth()->user()->hasAnyRole(['Admin', 'Super-admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Action non autorisée.'
            ], 403);
        }

        try {
            // Supprimer l'image associée si elle existe
            if ($event->image) {
                Storage::delete('public/' . $event->image);
            }
            
            // Supprimer l'événement
            $event->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'L\'événement a été supprimé avec succès.'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la suppression de l\'événement: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la suppression de l\'événement.'
            ], 500);
        }
    }


    /**
     * Affiche la liste des événements (admin uniquement), paginés par 5.
     * Vue : Events/AdminIndex
     */

    /*

    public function adminIndex()
    {
        if (!auth()->user()->hasAnyRole(['Admin', 'Super-admin'])) {
            return redirect()->back()->with('flash', [
                'error' => 'Vous n\'êtes pas autorisé à accéder à cette page.',
            ]);
        }

        $events = Event::with('creator:id,pseudo')
            ->orderBy('created_at', 'desc')
            ->paginate(5); // 5 événements par page

        return Inertia::render('Events/AdminIndex', [
            'events' => $events,
        ]);
    }

    */



    /**
     * Active ou désactive un événement (bascule le champ 'inactif').
     * Admin/Super-admin uniquement.
     */
    public function toggleActive(Event $event)
    {
        // Bascule entre actif et inactif
        $event->update(['inactif' => !$event->inactif]);

        return back()->with([
            'status' => 'success',
            'message' => $event->inactif
                ? 'Événement désactivé avec succès.'
                : 'Événement activé avec succès.'
        ]);
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


}

