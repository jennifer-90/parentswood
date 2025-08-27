<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Application;
use Inertia\Inertia;
use App\Models\User;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileController;
use App\Models\Event;
use Carbon\Carbon;

// ========================================================================
// ZONE PUBLIQUE (accessible à tous)
// ========================================================================

// Page d’accueil (redirige vers dashboard si déjà connecté)
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    $today   = now()->toDateString();   // "YYYY-MM-DD"
    $nowTime = now()->format('H:i');    // "HH:MM"  << IMPORTANT si ta colonne hour est "HH:MM"

    $events = Event::query()
        ->where('inactif', false)
        ->where(function ($q) use ($today, $nowTime) {
            $q->whereDate('date', '>', $today)
                ->orWhere(function ($q) use ($today, $nowTime) {
                    $q->whereDate('date', $today)
                        ->where('hour', '>=', $nowTime); // compare "HH:MM" avec "HH:MM"
                });
        })
        ->select(['id', 'name_event', 'date', 'hour', 'location', 'description'])
        ->withCount('participants')       // -> participants_count
        ->orderBy('date', 'asc')
        ->orderBy('hour', 'asc')
        ->limit(6)
        ->get()
        ->map(function ($event) {
            // Normaliser en ISO pour le JS (Safari-safe)
            // date -> "YYYY-MM-DD"
            $date = $event->date instanceof \Carbon\Carbon
                ? $event->date->format('Y-m-d')
                : (string) $event->date;

            // hour -> "HH:MM" -> on ajoute ":00" pour avoir "HH:MM:SS"
            $hour = trim((string) $event->hour);
            if ($hour && strlen($hour) === 5) {
                $hour .= ':00';
            }
            // ISO final: "YYYY-MM-DDTHH:MM:SS"
            $isoStart = ($date && $hour) ? "{$date}T{$hour}" : null;

            return [
                'id'                 => $event->id,
                'title'              => $event->name_event,
                'start_date'         => $isoStart, // <- clé conservée, format ISO
                'location'           => $event->location,
                'participants_count' => $event->participants_count,
            ];
        });

    return Inertia::render('Home/HomePage', [
        'canLogin'       => Route::has('login'),
        'canRegister'    => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion'     => PHP_VERSION,
        'events'         => $events,
    ]);
})->name('home');



// Vérification de disponibilité d’un pseudo (AJAX public)
Route::get('/check-pseudo/{pseudo}', function ($pseudo) {
    $available = ! User::where('pseudo', $pseudo)->exists();
    return response()->json(['available' => $available]);
})->name('pseudo.check');


// Route Authentification Breeze - Import des routes Breeze (login, register, etc.)
//require __DIR__.'/auth.php';


// ===========================================================================
// ZONE INVITES (guest uniquement)
// ==> Pages d'auth lorsqu'on n'est pas connecté
// ===========================================================================

Route::middleware('guest')->group(function () {
    // Connexion
    Route::get('/login',  [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);

    // Inscription
    Route::get('/register',  [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);
});

// ================================================================================
// ZONE AUTHENTIFIEE + ACTIVE
// ==> Accessible uniquement si connecté et compte actif
// ================================================================================

Route::middleware(['auth', 'active'])->group(function () {

    // Déconnexion (POST)
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    //Profil (mon compte)
    Route::get('/profile',          [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',        [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/pseudo',         [ProfileController::class, 'updatePseudo'])->name('profile.updatePseudo');
    Route::patch('/password',       [ProfileController::class, 'updatePassword'])->name('profile.updatePassword');
    Route::delete('/profile',       [ProfileController::class, 'destroy'])->name('profile.destroy'); // A REVOIR| chanbger en desative

    //Profils publics (des autres)
    Route::get('/users/{user:pseudo}', [UserController::class, 'show'])->name('users.show');

    //Events — zone utilisateur
    Route::prefix('events')->name('events.')->group(function () {
        // Listing / Création / Détail
        Route::get('/',          [EventController::class, 'index'])->name('index');
        Route::get('/create',    [EventController::class, 'create'])->name('create');
        Route::post('/',         [EventController::class, 'store'])->name('store');

        Route::get('/{event}',   [EventController::class, 'show'])->name('show');

        // Edition / Mise à jour
        Route::get('/{event}/edit', [EventController::class, 'edit'])->name('edit');
        Route::put('/{event}',      [EventController::class, 'update'])->name('update');

        // Commentaires (sur un event)
        Route::post('/{event}/messages', [MessageController::class, 'store'])->name('messages.store');

        // Rejoindre / toggle participation
        Route::post('/{event}/join',                 [EventController::class, 'join'])->name('join');
        Route::post('/{event}/toggle-participation', [EventController::class, 'toggleParticipation'])->name('toggleParticipation');

        // Désactiver son propre event (si autorisé par le contrôleur)
        Route::put('/{event}/deactivate', [EventController::class, 'deactivate'])->name('deactivate');

        // Annuler un event (créateur uniquement — logique dans le contrôleur)
        Route::post('/{event}/cancel', [EventController::class, 'cancel'])->name('cancel');

        // Signaler un event (1x par user/session — logique côté contrôleur)
        Route::post('/{event}/report', [EventController::class, 'report'])->name('report');
    });
}); //### - Fin du middleware : auth + active ***


// ======================
// ZONE ADMIN (Admin + Super-admin autorisés)
// ======================
Route::middleware(['auth', 'active', 'admin'])->group(function () {

    // Tableau de bord d’admin
    Route::get('/admin', [UserController::class, 'adminDashboard'])->name('admin.index');

    // Bouton active/désactive && changement de role users
    Route::post('/users/{user:id}/toggle-activation', [UserController::class, 'toggleActivation'])->name('users.toggleActivation');
    Route::post('/users/{user:id}/update-role',       [UserController::class, 'updateRole'])->name('users.updateRole');

    // Exports CSV
    Route::get('/admin/export/users',  [UserController::class, 'exportUsers'])->name('admin.export.users');
    Route::get('/admin/export/events', [EventController::class, 'exportEvents'])->name('admin.export.events');

    //Gestion events
    Route::post('/events/{event}/toggle-active', [EventController::class, 'toggleActive'])->name('events.toggleActive');
    Route::post('/events/{event}/accept',        [EventController::class, 'accept'])->name('events.accept');
    Route::post('/events/{event}/refuse',        [EventController::class, 'refuse'])->name('events.refuse');

    // Réinitialiser le compteur de signalements
    Route::post('/events/{event}/reports/clear', [EventController::class, 'clearReports'])->name('events.reports.clear');

}); //### - Fin du middleware : auth + active + admin ***


// ================================================================================
//ZONE SUPER-ADMIN
// ==> Nécessite auth + active + superadmin
// anonymisation + seed
// ================================================================================

Route::middleware(['auth', 'active', 'superadmin'])->group(function () {
    Route::post('/users/{user:id}/anonymize', [UserController::class, 'anonymize'])->name('users.anonymize');
    Route::post('/admin/seed/users',       [UserController::class, 'seedUsers'])->name('admin.seed.users');
});//### - Fin du middleware : auth + active + superadmin ***



// Route PATCH protégée par auth
Route::patch('/profile/deactivate', [\App\Http\Controllers\ProfileController::class, 'deactivateYourself'])
    ->middleware('auth')
    ->name('profile.deactivate');


Route::middleware('auth')->group(function () {
    Route::post('/users/{user:id}/block',   [UserController::class, 'block'])->name('users.block');
    Route::delete('/users/{user:id}/block', [UserController::class, 'unblock'])->name('users.unblock');
    // (optionnel) liste de mes utilisateurs bloqués
    Route::get('/me/blocked',            [UserController::class, 'blockedList'])->name('users.blocked');
});


Route::post('/support/contact', [UserController::class, 'sendAdminMessage'])
    ->middleware(['auth', 'throttle:5,1']) //
    ->name('users.sendAdminMessage');
