<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MessageController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        return inertia('Dashboard');
    })->name('dashboard');
    Route::get('/chat', function () {
        return inertia('chat');
    })->name('chat');
    Route::get('/users', [MessageController::class, 'allusers'])->name('allusers');
    Route::get('/messages', [MessageController::class, 'fetchMessages']);
    Route::post('/send-message', [MessageController::class, 'sendMessage']);
    Route::post('/delete-message/{id}', [MessageController::class, 'deleteMessage']);
    Route::post('/messages/{message}/reply', [MessageController::class, 'store']);

});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile/update', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
