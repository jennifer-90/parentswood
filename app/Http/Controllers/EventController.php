<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Message;

class EventController extends Controller
{
    public function index()
    {
        $events = Event::where('inactif', false)->latest()->paginate(10);
        return Inertia::render('Events/Index', ['events' => $events]);
    }

    public function create()
    {
        return Inertia::render('Events/Create');
    }

    public function store(Request $request)
    {
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

        if ($request->hasFile('picture_event')) {
            $validated['picture_event'] = $request->file('picture_event')->store('events', 'public');
        }

        $validated['created_by'] = auth()->id();

        Event::create($validated);

        return redirect()->route('events.index')->with('success', 'Événement créé !');
    }

    public function join(Event $event)
    {
        $event->participants()->attach(auth()->id());
        return back()->with('success', 'Tu as rejoint cet évènement !');
    }

    public function show(Event $event)
    {
        $messages = Message::where('event_id', $event->id)
            ->with('user:id,pseudo')
            ->latest()
            ->get();

        return Inertia::render('Events/Show', [
            'event' => $event->load('creator'),
            'messages' => $messages,
        ]);
    }


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



}
