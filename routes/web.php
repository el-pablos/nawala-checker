<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Tools\NawalaChecker\DashboardController;
use App\Http\Controllers\Tools\NawalaChecker\TargetsController;
use App\Http\Controllers\Tools\NawalaChecker\ShortlinksController;

Route::get('/', function () {
    return view('welcome');
});

// Nawala Checker Routes
Route::prefix('nawala-checker')->name('nawala-checker.')->middleware(['auth'])->group(function () {
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Targets
    Route::prefix('targets')->name('targets.')->group(function () {
        Route::get('/', [TargetsController::class, 'index'])->name('index');
        Route::get('/create', [TargetsController::class, 'create'])->name('create');
        Route::post('/', [TargetsController::class, 'store'])->name('store')->middleware('rate.limit:create-target,10,1');
        Route::get('/{target}', [TargetsController::class, 'show'])->name('show');
        Route::get('/{target}/edit', [TargetsController::class, 'edit'])->name('edit');
        Route::put('/{target}', [TargetsController::class, 'update'])->name('update');
        Route::delete('/{target}', [TargetsController::class, 'destroy'])->name('destroy');
        Route::post('/{target}/run-check', [TargetsController::class, 'runCheck'])->name('run-check')->middleware('rate.limit:run-check,5,1');
        Route::post('/{target}/toggle', [TargetsController::class, 'toggle'])->name('toggle');
    });

    // Shortlinks
    Route::prefix('shortlinks')->name('shortlinks.')->group(function () {
        Route::get('/', [ShortlinksController::class, 'index'])->name('index');
        Route::get('/create', [ShortlinksController::class, 'create'])->name('create');
        Route::post('/', [ShortlinksController::class, 'store'])->name('store')->middleware('rate.limit:create-shortlink,10,1');
        Route::get('/{shortlink}', [ShortlinksController::class, 'show'])->name('show');
        Route::delete('/{shortlink}', [ShortlinksController::class, 'destroy'])->name('destroy');
        Route::post('/{shortlink}/rotate', [ShortlinksController::class, 'rotate'])->name('rotate')->middleware('rate.limit:rotate,10,1');
        Route::post('/{shortlink}/rollback', [ShortlinksController::class, 'rollback'])->name('rollback')->middleware('rate.limit:rollback,10,1');
    });
});
