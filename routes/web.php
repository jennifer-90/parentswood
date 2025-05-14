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

// =====================================================
// Pages publiques
// =====================================================

// -- Page d'accueil (accessible uniquement aux invités)
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return Inertia::render('Home/HomePage', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('home');

// -- Vérification AJAX disponibilité du pseudo
Route::get('/check-pseudo/{pseudo}', function ($pseudo) {
    $available = !User::where('pseudo', $pseudo)->exists();
    return response()->json(['available' => $available]);
})->name('pseudo.check');

// -- Authentification Breeze
require __DIR__.'/auth.php';

// =====================================================
// Authentification : Login et Register (accessible aux invités uniquement)
// =====================================================
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);

    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);
});

// =====================================================
// Routes protégées par authentification
// =====================================================
Route::middleware(['auth'])->group(function () {

    // -- Tableau de bord
    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('verified')->name('dashboard');

    // -- Profil utilisateur
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // -- Profil public d'un utilisateur
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');

    // -- Commentaires d'événements
    Route::post('/events/{event}/messages', [MessageController::class, 'store'])->name('messages.store');

    // -- Rejoindre un événement
    Route::post('/events/{event}/join', [EventController::class, 'join'])->name('events.join');
});

// =====================================================
// Gestion des utilisateurs (réservée à Admin & Super-admin)
// =====================================================
Route::middleware(['auth'])->group(function () {
    Route::post('/users/{user}/toggle-activation', [UserController::class, 'toggleActivation'])->name('users.toggleActivation');
    Route::post('/users/{user}/update-role', [UserController::class, 'updateRole'])->name('users.updateRole');
});

// -- Anonymisation d'un utilisateur (Super-admin uniquement)
Route::middleware(['auth', 'superadmin'])->post('/users/{user}/anonymize', [UserController::class, 'anonymize'])->name('users.anonymize');

// =====================================================
// Gestion des événements (réservée aux utilisateurs connectés)
// =====================================================
Route::middleware(['auth'])->group(function () {
    Route::get('/events', [EventController::class, 'index'])->name('events.index');
    Route::get('/events/create', [EventController::class, 'create'])->name('events.create');
    Route::post('/events', [EventController::class, 'store'])->name('events.store');
});

// -- Détail d'un événement (accessible à tous)
Route::get('/events/{event}', [EventController::class, 'show'])->name('events.show');

// =====================================================
// Section Administration (Utilisateurs + Événements)
// =====================================================
Route::middleware(['auth'])->group(function () {
    Route::get('/admin', [UserController::class, 'adminDashboard'])->name('admin.index');
});

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



Route::post('/events', [EventController::class, 'store'])->name('events.store');

Route::post('/events/{event}/toggle-participation', [EventController::class, 'toggleParticipation'])->name('events.toggleParticipation');




