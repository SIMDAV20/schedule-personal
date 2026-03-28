<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\DistrictController;
use App\Http\Controllers\AvailabilityController;
use App\Http\Controllers\ReservationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (!auth()->check()) {
        return redirect()->route('login');
    }

    if (auth()->user()->isAdmin()) {
        return redirect()->route('admin.schedule');
    }

    return redirect()->route('schedule');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/schedule', [ScheduleController::class, 'index'])->name('admin.schedule');
    
        // District management
        Route::get('/districts', [DistrictController::class, 'index'])->name('admin.districts');
        Route::post('/districts', [DistrictController::class, 'store']);
        Route::delete('/districts/{district}', [DistrictController::class, 'destroy']);

        Route::get('/reservations', [ReservationController::class, 'index'])->name('admin.reservations');
        Route::post('/reservations', [ReservationController::class, 'store']);
        Route::put('/reservations/{reservation}/assign', [ReservationController::class, 'assign']);
        Route::delete('/reservations/{reservation}', [ReservationController::class, 'destroy']);
    });
    
    Route::middleware('role:paseador')->group(function () {
        Route::get('/schedule', [AvailabilityController::class, 'index'])->name('schedule');
        Route::post('/availability', [AvailabilityController::class, 'store']);
        Route::put('/availability/{availability}', [AvailabilityController::class, 'update']);
        Route::delete('/availability/{availability}', [AvailabilityController::class, 'destroy']);

        Route::get('/reservations', [ReservationController::class, 'index'])->name('reservations');
    });
});



require __DIR__.'/auth.php';
