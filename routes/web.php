<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Tools\NawalaChecker\DashboardController;
use App\Http\Controllers\Tools\NawalaChecker\TargetsController;
use App\Http\Controllers\Tools\NawalaChecker\ShortlinksController;
use App\Http\Controllers\Admin\AdminController;

Route::get('/', function () {
    return view('welcome');
});

// Authentication Routes (for testing)
Route::get('/login', function () {
    return inertia('Auth/Login');
})->name('login')->middleware('guest');

Route::post('/login', function (\Illuminate\Http\Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    if (Auth::attempt($credentials, $request->boolean('remember'))) {
        $request->session()->regenerate();
        return redirect()->intended('/nawala-checker');
    }

    return back()->withErrors([
        'email' => 'The provided credentials do not match our records.',
    ])->onlyInput('email');
})->middleware('guest');

Route::post('/logout', function (\Illuminate\Http\Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/login');
})->name('logout')->middleware('auth');

// Admin Panel Routes
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::get('/users/{user}', [AdminController::class, 'showUser'])->name('users.show');
    Route::post('/users', [AdminController::class, 'createUser'])->name('users.create');
    Route::put('/users/{user}', [AdminController::class, 'updateUser'])->name('users.update');
    Route::delete('/users/{user}', [AdminController::class, 'deleteUser'])->name('users.delete');
    Route::post('/users/{user}/suspend', [AdminController::class, 'suspendUser'])->name('users.suspend');
    Route::post('/users/{user}/reactivate', [AdminController::class, 'reactivateUser'])->name('users.reactivate');
    Route::get('/statistics', [AdminController::class, 'statistics'])->name('statistics');
    Route::get('/targets', [AdminController::class, 'allTargets'])->name('targets');
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
