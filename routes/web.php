<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SavingsController;
use App\Http\Controllers\PayrollPdfController;
use App\Http\Controllers\LoanSummaryController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

});

Route::get('/payroll/pdf/{id}', [PayrollPdfController::class, 'generatePayslip'])->name('payroll.pdf');
Route::get('/loan-summary/{loan}', [LoanSummaryController::class, 'generate'])->name('loan.summary');
Route::get('savings/{planId}/summary', [SavingsController::class, 'generate'])
    ->name('savings.summary');

require __DIR__.'/auth.php';
