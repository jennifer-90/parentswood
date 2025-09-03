<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user    = Auth::user();
        $isStaff = $user->hasAnyRole(['Admin', 'Super-admin']);

        // -------- Timezone cohérente avec le front --------
        $tz      = 'Europe/Brussels';
        $now     = Carbon::now($tz);
        $today   = $now->toDateString();      // YYYY-MM-DD
        $nowTime = $now->format('H:i:s');     // HH:mm:ss

        // -------- Calendrier : tous les actifs + MES events (même en attente/inactifs) --------
        $allEvents = Event::select('id','name_event','date','hour','location','description','created_by','inactif')
            ->where(function ($q) use ($user) {
                $q->where('inactif', 0)
                    ->orWhere('created_by', $user->id);
            })
            ->orderBy('date')->orderBy('hour')
            ->get()
            ->map(function ($e) {
                $date = $e->date instanceof Carbon ? $e->date->toDateString() : (string) $e->date;
                $hour = $e->hour ? (strlen($e->hour) === 5 ? $e->hour . ':00' : $e->hour) : null;

                return [
                    'id'         => $e->id,
                    'title'      => $e->name_event,
                    'start'      => $hour ? ($date . 'T' . $hour) : $date, // FullCalendar
                    // pour le front :
                    'name_event' => $e->name_event,
                    'date'       => $date,
                    'hour'       => $hour,
                    'location'   => $e->location,
                    'description'=> $e->description,
                    'created_by' => $e->created_by,
                    'inactif'    => (bool) $e->inactif,
                    'url'        => route('events.show', $e->id),
                ];
            });

        // -------- Participations de l'utilisateur --------
        $userParticipatedEvents = $user->eventsParticipated()
            ->select('events.id','events.name_event','events.date','events.hour','events.location','events.description')
            ->where('events.inactif', 0)
            ->orderBy('events.date','asc')->orderBy('events.hour','asc')
            ->get()
            ->map(function ($e) {
                $date = $e->date instanceof Carbon ? $e->date->toDateString() : (string) $e->date;
                $hour = $e->hour ? (strlen($e->hour) === 5 ? $e->hour . ':00' : $e->hour) : '00:00:00';
                return [
                    'id'          => $e->id,
                    'name_event'  => $e->name_event,
                    'date'        => $date,
                    'hour'        => $hour,
                    'location'    => $e->location,
                    'description' => $e->description,
                ];
            });

        // -------- Split à venir / passés (date + heure) --------
        $upcomingEvents = $userParticipatedEvents->filter(function ($e) use ($today, $nowTime) {
            return ($e['date'] > $today) || ($e['date'] === $today && $e['hour'] >= $nowTime);
        })->sortBy([
            ['date', 'asc'],
            ['hour', 'asc'],
        ])->values();

        $pastEvents = $userParticipatedEvents->filter(function ($e) use ($today, $nowTime) {
            return ($e['date'] < $today) || ($e['date'] === $today && $e['hour'] < $nowTime);
        })->sortByDesc(function ($e) {
            return $e['date'] . ' ' . $e['hour'];
        })->values();

        // -------- Compteurs / stats --------
        $userCreatedEventsCount = Event::where('created_by', $user->id)->count();

        // Nombre d'événements FUTURS & ACTIFS créés par moi (utile pour jauge)
        $createdActiveCount = Event::where('created_by', $user->id)
            ->whereNull('cancelled_at')
            ->where('inactif', false)
            ->where(function ($q) use ($today, $nowTime) {
                $q->whereDate('date', '>', $today)
                    ->orWhere(function ($qq) use ($today, $nowTime) {
                        $qq->whereDate('date', $today)
                            ->where('hour', '>=', $nowTime);
                    });
            })
            ->count();

        // Limite d'événements actifs (si tu as un champ en DB, sinon 10)
        $maxActiveSlots = (int) ($user->max_create_event ?? 10);

        $currentMonth = $now->month;
        $currentYear  = $now->year;

        $eventsThisMonth = $upcomingEvents->filter(function ($e) use ($currentMonth, $currentYear, $tz) {
            $d = Carbon::parse($e['date'], $tz);
            return $d->month === $currentMonth && $d->year === $currentYear;
        })->count();

        $nextEvent = $upcomingEvents->first();

        // -------- Plotly : data des 6 prochains mois (évents auxquels je participe) --------
        $chartData  = [];
        $startMonth = $now->copy()->startOfMonth();

        for ($i = 0; $i < 6; $i++) {
            $month      = $startMonth->copy()->addMonths($i);
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd   = $month->copy()->endOfMonth();

            $count = $upcomingEvents->filter(function ($e) use ($monthStart, $monthEnd, $tz) {
                $d = Carbon::parse($e['date'], $tz);
                return $d->between($monthStart, $monthEnd);
            })->count();

            $chartData[] = [
                'month' => $month->locale('fr')->translatedFormat('M Y'), // ex: "sept. 2025"
                'count' => $count,
            ];
        }

        // -------- Utilisateurs que JE bloque  --------
        $blockedUsers = $user->blocks()
            ->select('users.id', 'users.pseudo', 'users.first_name', 'users.last_name', 'users.picture_profil')
            ->get()
            ->map(function ($u) {
                $url = $u->picture_profil_url
                    ?? ($u->picture_profil ? Storage::disk('public')->url($u->picture_profil) : null);

                return [
                    'id'                  => $u->id,
                    'pseudo'              => $u->pseudo,
                    'first_name'          => $u->first_name,
                    'last_name'           => $u->last_name,
                    'picture_profil_url'  => $url,
                ];
            });

        return Inertia::render('Dashboard', [
            'user'                   => $user,
            'allEvents'              => $allEvents,
            'upcomingEvents'         => $upcomingEvents,
            'pastEvents'             => $pastEvents,
            'userCreatedEventsCount' => $userCreatedEventsCount,
            'isStaff'                => $isStaff,
            'chartData'              => $chartData,

            // Stats “header”
            'stats' => [
                'eventsThisMonth' => $eventsThisMonth,
                'nextEvent'       => $nextEvent,
                'totalUpcoming'   => $upcomingEvents->count(),
                'totalPast'       => $pastEvents->count(),
            ],

            'blockedUsers'          => $blockedUsers,
            'createdActiveCount'    => $createdActiveCount,
            'maxActiveSlots'        => $maxActiveSlots,
        ]);
    }
}
