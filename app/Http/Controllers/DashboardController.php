<?php

namespace App\Http\Controllers;

/*use App\Models\Event;
use App\Models\Message;*/
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Affiche le tableau de bord de l'utilisateur connecté.
     */
    public function index()
    {
        // Récupération des données nécessaires


        /*$user = Auth::user();
        $events = Event::where('user_id', $user->id)->get();
        $messages = Message::where('recipient_id', $user->id)->get();*/

        // Transmission des données au frontend via Inertia
        return Inertia::render('Dashboard', [
            'user'=>Auth::user(),


        /*$events = Event::where('user_id', $user->id)->get();
        $messages = Message::where('recipient_id', $user->id)->get();*/


        ]);
    }
}
