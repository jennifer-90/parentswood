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

// ========================================================================
// Pages|Routes - publiques
// ========================================================================

// #################### Page d'accueil (invités uniquement) ####################
use App\Models\Event;
use Carbon\Carbon;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    // Récupérer les événements à venir
    $events = Event::where('inactif', 0)
        ->where('date', '>=', Carbon::now())
        ->orderBy('date', 'asc')
        ->take(6) // afficher que 6évents
        ->get()
        ->map(function($event) {
            return [
                'id' => $event->id,
                'title' => $event->name_event,
                'start_date' => $event->date . ' ' . $event->hour,
                'location' => $event->location,
                'participants_count' => $event->participants()->count(),
                'description' => $event->description
            ];
        });

    return Inertia::render('Home/HomePage', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
        'events' => $events  // Ajoutez cette ligne
    ]);
})->name('home');


// Vérification pseudo
Route::get('/check-pseudo/{pseudo}', function ($pseudo) {
    $available = !User::where('pseudo', $pseudo)->exists();
    return response()->json(['available' => $available]);
})->name('pseudo.check');


// Route Authentification Breeze - Import des routes Breeze (login, register, etc.)
require __DIR__.'/auth.php';


// ===========================================================================
// Routes invitées (guest)
// ===========================================================================

Route::middleware('guest')->group(function () {
    // Formulaire de connexion
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);

    // Formulaire d'inscription
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);
});

// ================================================================================
// Routes authentifiées (auth)
// ================================================================================
Route::middleware(['auth'])->group(function () {

    // Tableau de bord
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profil utilisateur : affichage, mise à jour, suppression
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy'); /* a revoir pour anonymiser*/
    Route::patch('/pseudo', [ProfileController::class, 'updatePseudo'])->name('profile.updatePseudo');
    Route::patch('/password', [ProfileController::class, 'updatePassword'])->name('profile.updatePassword');



    // Profil d'un utilisateur- voir un profil d'un autre user
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');

    // Events
    Route::middleware(['auth'])->group(function () {
        Route::get('/events', [EventController::class, 'index'])->name('events.index');
        Route::get('/events/create', [EventController::class, 'create'])->name('events.create');
        Route::post('/events', [EventController::class, 'store'])->name('events.store');
        Route::get('/events/{event}', [EventController::class, 'show'])->name('events.show');
        // Commentaires sur les événements
        Route::post('/events/{event}/messages', [MessageController::class, 'store'])->name('messages.store');
        // -- Rejoindre un événement
        Route::post('/events/{event}/join', [EventController::class, 'join'])->name('events.join');
    });

});


//Route::post('/events', [EventController::class, 'store'])->name('events.store');
//Route::post('/events/{event}/toggle-participation', [EventController::class, 'toggleParticipation'])->name('events.toggleParticipation');
//Route::get('/events/{event}', [EventController::class, 'show'])->name('events.show');


// ======================================================================================
// Section Administration (Utilisateurs + Événements)
// ======================================================================================

//users
Route::middleware(['auth'])->group(function () {

    Route::post('/users/{user}/toggle-activation', [UserController::class, 'toggleActivation'])->name('users.toggleActivation');
    Route::post('/users/{user}/update-role', [UserController::class, 'updateRole'])->name('users.updateRole');
});

// Anonymisation d'un utilisateur (Super-admin uniquement)
Route::middleware(['auth', 'superadmin'])->post('/users/{user}/anonymize', [UserController::class, 'anonymize'])->name('users.anonymize');


Route::middleware(['auth'])->group(function () {
    Route::get('/admin', [UserController::class, 'adminDashboard'])->name('admin.index');
});

//events
Route::middleware(['auth'])->group(function () {
    Route::post('/events/{event}/toggle-active', [EventController::class, 'toggleActive'])->name('events.toggleActive');
});

Route::middleware(['auth'])->group(function () {
    Route::post('/events/{event}/accept', [EventController::class, 'accept'])->name('events.accept');
    Route::post('/events/{event}/refuse', [EventController::class, 'refuse'])->name('events.refuse');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/admin/export/users', [UserController::class, 'exportUsers'])->name('admin.export.users');
    Route::get('/admin/export/events', [EventController::class, 'exportEvents'])->name('admin.export.events');
});

Route::middleware(['auth'])->group(function () {
    // Formulaire d’édition
    Route::get('/events/{event}/edit', [EventController::class, 'edit'])->name('events.edit');

    // Mise à jour
    Route::post('/events/{event}', [EventController::class, 'update'])->name('events.update');
});







