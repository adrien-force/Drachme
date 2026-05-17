<?php

declare(strict_types=1);

use App\Http\Controllers\AccountController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CategoryRuleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ImportProviderController;
use App\Http\Controllers\ShellPlaceholderController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\RecurringController;
use App\Http\Controllers\TransferController;
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
    Route::post('transactions/apply-category-rules', [TransactionController::class, 'applyCategoryRules'])
        ->name('transactions.apply-category-rules');
    Route::get('transactions/{transaction}/edit', [TransactionController::class, 'edit'])->name('transactions.edit');
    Route::put('transactions/{transaction}', [TransactionController::class, 'update'])->name('transactions.update');
    Route::patch('transactions/{transaction}/category', [TransactionController::class, 'updateCategory'])
        ->name('transactions.update-category');
    Route::post('transactions/{transaction}/recurring', [TransactionController::class, 'markRecurring'])
        ->name('transactions.mark-recurring');
    Route::delete('transactions/{transaction}/recurring', [TransactionController::class, 'unmarkRecurring'])
        ->name('transactions.unmark-recurring');
    Route::get('transfers', [TransferController::class, 'index'])->name('transfers.index');
    Route::post('transfers/accept', [TransferController::class, 'accept'])->name('transfers.accept');
    Route::post('transfers/dismiss', [TransferController::class, 'dismiss'])->name('transfers.dismiss');
    Route::post('transfers', [TransferController::class, 'store'])->name('transfers.store');
    Route::get('recurring', [RecurringController::class, 'index'])->name('recurring.index');
    Route::post('recurring/confirm', [RecurringController::class, 'confirm'])->name('recurring.confirm');
    Route::post('recurring/dismiss', [RecurringController::class, 'dismiss'])->name('recurring.dismiss');
    Route::delete('recurring/{recurringPattern}', [RecurringController::class, 'destroy'])->name('recurring.destroy');
    Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::put('categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
    Route::get('category-rules', [CategoryRuleController::class, 'index'])->name('category-rules.index');
    Route::post('category-rules', [CategoryRuleController::class, 'store'])->name('category-rules.store');
    Route::post('category-rules/from-label', [CategoryRuleController::class, 'storeFromLabel'])
        ->name('category-rules.store-from-label');
    Route::post('category-rules/test-match', [CategoryRuleController::class, 'testMatch'])
        ->name('category-rules.test-match');
    Route::put('category-rules/{categoryRule}', [CategoryRuleController::class, 'update'])->name('category-rules.update');
    Route::delete('category-rules/{categoryRule}', [CategoryRuleController::class, 'destroy'])->name('category-rules.destroy');
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
