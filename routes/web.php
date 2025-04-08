<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Models\User;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Route pour la page d'accueil
Route::get('/', function () {

    if (Auth::check()) {
        return redirect()->route('dashboard'); // Redirige vers dashboard si connecté
    }

    return Inertia::render('Home/HomePage', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('home');



// Route pour le tableau de bord
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Routes spécifiques aux invités
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);
});



// Vérification de pseudo
Route::get('/check-pseudo/{pseudo}', function ($pseudo) {
    $available = !User::where('pseudo', $pseudo)->exists();
    return response()->json(['available' => $available]);
})->name('pseudo.check');

// Inclusion des routes générées par Laravel Breeze
require __DIR__ . '/auth.php';

// Groupe de routes nécessitant une authentification
Route::middleware('auth')->group(function () {
    Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [App\Http\Controllers\ProfileController::class, 'destroy'])->name('profile.destroy');
});


Route::middleware(['auth'])->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
});


// Active ou inactive un user
Route::post('/users/{user}/toggle-activation', [UserController::class, 'toggleActivation'])
    ->middleware(['auth'])
    ->name('users.toggleActivation');



// Accès au tableau des utilisateurs → Admin ou Super-admin
Route::middleware(['auth', 'admin'])->get('/users', [UserController::class, 'index'])->name('users.index');
Route::middleware(['auth', 'superadmin'])->get('/users', [UserController::class, 'index'])->name('users.index');

// Désactiver un utilisateur → uniquement Super-admin
Route::middleware(['auth', 'superadmin'])->post('/users/{user}/toggle-activation', [UserController::class, 'toggleActivation'])->name('users.toggleActivation');


