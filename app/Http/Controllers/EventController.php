<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Message;

class EventController extends Controller
{
    /**
     * Affiche la liste des événements actifs (non inactifs), paginés par 10.
     * Vue : Events/Index
     */
    public function index()
    {
        $events = Event::where('inactif', false)->latest()->paginate(10);
        return Inertia::render('Events/Index', ['events' => $events]);
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
        // Validation des champs
        $validated = $request->validate([
            'name_event' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date' => 'required|date',
            'hour' => 'required',
            'location' => 'required|string|max:255',
            'min_person' => 'required|integer|min:1',
            'max_person' => 'required|integer|min:1',
            'picture_event' => 'nullable|image|max:2048',
        ]);

        // Gestion de l'image uploadée
        if ($request->hasFile('picture_event')) {
            $validated['picture_event'] = $request->file('picture_event')->store('events', 'public');
        }

        // Attribution de l'utilisateur créateur
        $validated['created_by'] = auth()->id();

        // Création de l'événement
        Event::create($validated);

        return redirect()->route('events.index')->with('success', 'Événement créé !');
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

        if ($user->eventsParticipated()->where('event_id', $event->id)->exists()) {
            $user->eventsParticipated()->detach($event->id);
            return back()->with('success', 'Participation annulée.');
        } else {
            $user->eventsParticipated()->attach($event->id);
            return back()->with('success', 'Participation confirmée.');
        }
    }




    /**
     * Affiche les détails d’un événement et les messages/commentaires associés.
     * Vue : Events/Show
     */
    public function show(Event $event)
    {
        $messages = Message::where('event_id', $event->id)
            ->with('user:id,pseudo')
            ->latest()
            ->get();

        return Inertia::render('Events/Show', [
            'event' => $event->load('creator', 'participants'),
            'messages' => $messages,
        ]);
    }


    /**
     * Affiche la liste des événements (admin uniquement), paginés par 20.
     * Vue : Events/AdminIndex
     */
    public function adminIndex()
    {
        if (!auth()->user()->hasAnyRole(['Admin', 'Super-admin'])) {
            return redirect()->back()->with('flash', [
                'error' => 'Vous n\'êtes pas autorisé à accéder à cette page.',
            ]);
        }

        $events = Event::with('creator:id,pseudo')
            ->orderBy('created_at', 'desc')
            ->paginate(20); // 20 événements par page

        return Inertia::render('Events/AdminIndex', [
            'events' => $events,
        ]);
    }

    /**
     * Active ou désactive un événement (bascule le champ 'inactif').
     * Admin/Super-admin uniquement.
     */
    public function toggleActive(Event $event) /* -- basculer actif --*/
    {
            if (!auth()->user()->hasAnyRole(['Admin', 'Super-admin'])) {
                return redirect()->back()->with('flash', [
                    'error' => 'Action non autorisée.',
                ]);
            }

            /* changement de l’état actif/inactif de l’évènt en un seul clic */
            $event->update(attributes: [
                'inactif' => !$event->inactif, /* --le contraire de inactif" -- */
                /*  Si inactif est actuellement TRUE (événement désactivé) ==> il devient false (événement activé) -- le bouton affiche "Activer" en js.
                    Si inactif est actuellement FALSE (événement activé) ==> il devient true (événement désactivé) -- le bouton affiche "Désactiver" en js.*/
            ]);

            return back()->with('success', 'Statut de l’événement mis à jour.');
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

