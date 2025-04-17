<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\UserController;
use App\Models\User;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;


// -------------------------
// Accueil (accessible uniquement si déconnecté)
// -------------------------
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard'); // Redirige vers le tableau de bord si l'utilisateur est connecté
    }

    return Inertia::render('Home/HomePage', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('home');


// -------------------------
// Tableau de bord (authentifié + email vérifié)
// -------------------------
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');


// -------------------------
// Authentification (routes accessibles aux invités uniquement)
// -------------------------
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);
});


// -------------------------
// Vérification AJAX du pseudo
// -------------------------
Route::get('/check-pseudo/{pseudo}', function ($pseudo) {
    $available = !User::where('pseudo', $pseudo)->exists();
    return response()->json(['available' => $available]);
})->name('pseudo.check');


// -------------------------
// Auth Laravel Breeze : profil utilisateur (auth requis)
// -------------------------
require __DIR__ . '/auth.php';

Route::middleware('auth')->group(function () {
    Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'edit'])
        ->name('profile.edit');
    Route::patch('/profile', [App\Http\Controllers\ProfileController::class, 'update'])
        ->name('profile.update');
    Route::delete('/profile', [App\Http\Controllers\ProfileController::class, 'destroy'])
        ->name('profile.destroy');
});


// -------------------------
// Gestion des utilisateurs (Admin et Super-admin)
// -------------------------
Route::middleware(['auth'])
    ->get('/users', [UserController::class, 'index'])
    ->name('users.index'); // Vérification du rôle effectuée dans le contrôleur via hasAnyRole(['Admin', 'Super-admin'])

Route::middleware(['auth'])->post('/users/{user}/toggle-activation', [UserController::class, 'toggleActivation'])
    ->name('users.toggleActivation'); // Vérification du rôle se fait dans le contrôleur

Route::middleware(['auth', 'superadmin'])->post('/users/{user}/anonymize', [UserController::class, 'anonymize'])
    ->name('users.anonymize');

Route::post('/users/{user}/update-role', [UserController::class, 'updateRole'])->name('users.updateRole');




// -------------------------
// Gestion des événements (authentification requise)
// -------------------------
Route::middleware(['auth'])->group(function () {
    Route::get('/events', [EventController::class, 'index'])
        ->name('events.index');
    Route::get('/events/create', [EventController::class, 'create'])
        ->name('events.create');
    Route::post('/events', [EventController::class, 'store'])
        ->name('events.store');
    Route::post('/events/{event}/join', [EventController::class, 'join'])
        ->name('events.join');
});

Route::get('/events/{event}', [EventController::class, 'show'])->name('events.show');


Route::middleware(['auth'])->group(function () {
    Route::post('/events/{event}/messages', [MessageController::class, 'store'])->name('messages.store');
});

Route::get('/users/{user}', [UserController::class, 'show'])
    ->middleware('auth')
    ->name('users.show');

