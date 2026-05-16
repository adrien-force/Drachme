<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ShellPlaceholderController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (! auth()->check()) {
        return redirect()->route('login');
    }

    return redirect()->route('dashboard');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('accounts', [ShellPlaceholderController::class, 'accounts'])->name('accounts.index');
    Route::get('transactions', [ShellPlaceholderController::class, 'transactions'])->name('transactions.index');
    Route::get('providers', [ShellPlaceholderController::class, 'providers'])->name('providers.index');
    Route::get('import', [ShellPlaceholderController::class, 'import'])->name('import.index');
    Route::get('investments', [ShellPlaceholderController::class, 'investments'])->name('investments.index');
});

require __DIR__.'/settings.php';
