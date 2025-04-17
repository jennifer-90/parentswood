<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Event;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function store(Request $request, Event $event)
    {
        $request->validate([
            'text' => 'required|string|max:1000',
        ]);

        Message::create([
            'user_id' => auth()->id(),
            'event_id' => $event->id,
            'text' => $request->text,
        ]);

        return back();
    }
}
