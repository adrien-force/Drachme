<?php

declare(strict_types=1);

use App\Http\Controllers\AccountController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ImportProviderController;
use App\Http\Controllers\ShellPlaceholderController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (! auth()->check()) {
        return redirect()->route('login');
    }

    return redirect()->route('dashboard');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('accounts', [AccountController::class, 'index'])->name('accounts.index');
    Route::get('accounts/create', [AccountController::class, 'create'])->name('accounts.create');
    Route::get('accounts/{account}', [AccountController::class, 'show'])->name('accounts.show');
    Route::post('accounts', [AccountController::class, 'store'])->name('accounts.store');
    Route::get('accounts/{account}/edit', [AccountController::class, 'edit'])->name('accounts.edit');
    Route::put('accounts/{account}', [AccountController::class, 'update'])->name('accounts.update');
    Route::delete('accounts/{account}', [AccountController::class, 'destroy'])->name('accounts.destroy');
    Route::get('transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('transactions/create', [TransactionController::class, 'create'])->name('transactions.create');
    Route::post('transactions', [TransactionController::class, 'store'])->name('transactions.store');
    Route::get('transactions/{transaction}/edit', [TransactionController::class, 'edit'])->name('transactions.edit');
    Route::put('transactions/{transaction}', [TransactionController::class, 'update'])->name('transactions.update');
    Route::get('providers', [ImportProviderController::class, 'index'])->name('providers.index');
    Route::get('providers/create', [ImportProviderController::class, 'create'])->name('providers.create');
    Route::post('providers/detect-date-format', [ImportProviderController::class, 'detectDateFormat'])
        ->name('providers.detect-date-format');
    Route::post('providers/preview', [ImportProviderController::class, 'preview'])->name('providers.preview');
    Route::post('providers', [ImportProviderController::class, 'store'])->name('providers.store');
    Route::get('providers/{importProvider}', [ImportProviderController::class, 'show'])->name('providers.show');
    Route::get('providers/{importProvider}/edit', [ImportProviderController::class, 'edit'])->name('providers.edit');
    Route::put('providers/{importProvider}', [ImportProviderController::class, 'update'])->name('providers.update');
    Route::delete('providers/{importProvider}', [ImportProviderController::class, 'destroy'])->name('providers.destroy');
    Route::get('import', [ImportController::class, 'create'])->name('import.index');
    Route::post('import', [ImportController::class, 'store'])->name('import.store');
    Route::get('import/{importBatch}', [ImportController::class, 'show'])->name('import.show');
    Route::post('import/{importBatch}/parse', [ImportController::class, 'parse'])->name('import.parse');
    Route::post('import/{importBatch}/commit', [ImportController::class, 'commit'])->name('import.commit');
    Route::delete('import/{importBatch}', [ImportController::class, 'destroy'])->name('import.destroy');
    Route::get('investments', [ShellPlaceholderController::class, 'investments'])->name('investments.index');
});

require __DIR__.'/settings.php';
