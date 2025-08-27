<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Carbon\Carbon; //Bibiliothèque laravel - Gestion heures & dates ||  extension de l’API PHP pour DateTime

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $isStaff = $user->hasAnyRole(['Admin','Super-admin']);

        // Requete de récupération de tous les événements pour le calendrier
        $allEvents = Event::select('id', 'name_event', 'date', 'hour', 'location', 'description')
            ->where('inactif', 0) //condition - que les events actifs || WHERE inactif = 0
            ->get() // execute la requete
            ->map(function ($event) { // transforme chaque objet Event en un tableau
                return [
                    'id' => $event->id,
                    'title' => $event->name_event,
                    'start' => $event->date,
                    'time' => $event->hour,
                    'location' => $event->location,
                    'description' => $event->description,
                    'url' => route('events.show', ['event' => $event->id]),
                    ];
            });



        // Récupération des événements auxquels l'utilisateur participe
        $userParticipatedEvents = $user->eventsParticipated()
            ->select('events.id', 'events.name_event', 'events.date', 'events.hour', 'events.location', 'events.description')
            ->where('events.inactif', 0)
            ->orderBy('events.date', 'asc')
            ->orderBy('events.hour', 'asc')
            ->get()
            ->map(function ($event) {
                $eventDate = Carbon::parse($event->date); //Transformation en objet pour utiliser les fonctions
                $today = Carbon::today();

                return [
                    'id' => $event->id,
                    'name_event' => $event->name_event,
                    'date' => $event->date,
                    'hour' => $event->hour,
                    'location' => $event->location,
                    'description' => $event->description,
                    'is_past' => $eventDate->lt($today), //lt() = "less than" = inférieur à
                    'status' => $eventDate->lt($today) ? 'passé' : 'à_venir',
                ];
            });

        // Séparer les événements à venir et passés
        $upcomingEvents = $userParticipatedEvents->filter(function ($event) {
            return $event['status'] === 'à_venir';
        })->sortBy('date')->values();

        $pastEvents = $userParticipatedEvents->filter(function ($event) {
            return $event['status'] === 'passé';
        })->sortByDesc('date')->values();

        // Statistiques pour le bandeau récapitulatif
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        $eventsThisMonth = $upcomingEvents->filter(function ($event) use ($currentMonth, $currentYear) {
            // use => Permet à une fonction anonyme d’accéder à des variables externes
            $eventDate = Carbon::parse($event['date']);
            return $eventDate->month === $currentMonth && $eventDate->year === $currentYear;
        })->count();

        $nextEvent = $upcomingEvents->first();

        // ⚠️ Compteurs "créés par l'utilisateur"
        $userCreatedEventsCount = Event::where('created_by', $user->id)
            ->where('inactif', 0)
            ->count();

        // ⚠️ NOUVEAU : actifs / inactifs + liste des inactifs pour l'UI
        $createdActiveCount = Event::where('created_by', $user->id)
            ->where('inactif', 0)
            ->count(); // ⚠️

        $createdInactiveCount = Event::where('created_by', $user->id)
            ->where('inactif', 1)
            ->count(); // ⚠️

        $inactiveCreatedEvents = Event::select('id', 'name_event') // ⚠️
        ->where('created_by', $user->id)
            ->where('inactif', 1)
            ->latest('updated_at')
            ->take(10)
            ->get(); // ⚠️




        // Récupération du nombre d'événements créés par l'utilisateur
        $userCreatedEventsCount = Event::where('created_by', $user->id)
            ->where('inactif', 0)
            ->count();

        // Données pour le graphique des événements par mois (6 prochains mois) || lib plotly
        $chartData = [];
        $currentDate = Carbon::now();

        for ($i = 0; $i < 6; $i++) {
            $monthDate = $currentDate->copy()->addMonths($i);
            $monthName = $monthDate->locale('fr')->format('M Y');

            $eventsCount = $upcomingEvents->filter(function ($event) use ($monthDate) {
                $eventDate = Carbon::parse($event['date']);
                return $eventDate->month === $monthDate->month && $eventDate->year === $monthDate->year;
            })->count();

            $chartData[] = [
                'month' => $monthName,
                'count' => $eventsCount
            ];
        }



        $blockedUsers = $user->blocks()
            ->select('users.id','users.pseudo','users.first_name','users.last_name','users.picture_profil')
            ->orderBy('users.pseudo')
            ->get()
            ->map(fn($u) => [
                'id'                 => $u->id,
                'pseudo'             => $u->pseudo,
                'first_name'         => $u->first_name,
                'last_name'          => $u->last_name,
                'picture_profil_url' => $u->picture_profil
                    ? Storage::disk('public')->url($u->picture_profil)
                    : null,
            ]);


        return Inertia::render('Dashboard', [
            'user' => $user,
            'allEvents' => $allEvents,
            'upcomingEvents' => $upcomingEvents,
            'pastEvents' => $pastEvents,
            'userCreatedEventsCount' => $userCreatedEventsCount,
            'chartData' => $chartData,
            'blockedUsers' => $blockedUsers,
            'isStaff' => $isStaff,

            'createdActiveCount'   => $createdActiveCount,     // ⚠️
            'createdInactiveCount' => $createdInactiveCount,   // ⚠️
            'inactiveCreatedEvents'=> $inactiveCreatedEvents,  // ⚠️
            'maxActiveSlots'       => 10,                      // ⚠️

            'stats' => [
                'eventsThisMonth' => $eventsThisMonth,
                'nextEvent' => $nextEvent,
                'totalUpcoming' => $upcomingEvents->count(),
                'totalPast' => $pastEvents->count(),
            ],
        ]);
    }
}
