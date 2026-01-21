<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;

Route::get('/', function () {
    return redirect('/login');
});

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Users
    Route::resource('users', \App\Http\Controllers\UserController::class);
    
    // Clients
    Route::resource('clients', \App\Http\Controllers\ClientController::class);
    
    // Policies
    Route::resource('policies', \App\Http\Controllers\PolicyController::class);
    
    // Plans
    Route::resource('plans', \App\Http\Controllers\PlanController::class);
    
    // Service Categories (Products)
    Route::resource('service-categories', \App\Http\Controllers\ServiceCategoryController::class);
    
    // Pre-Authorizations
    Route::resource('pre-authorizations', \App\Http\Controllers\PreAuthorizationController::class);
    
    // Transactions
    Route::get('/transactions', [\App\Http\Controllers\TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/outstanding', [\App\Http\Controllers\TransactionController::class, 'outstanding'])->name('transactions.outstanding');
    Route::get('/transactions/cleared', [\App\Http\Controllers\TransactionController::class, 'cleared'])->name('transactions.cleared');
    
    // Invoices
    Route::resource('invoices', \App\Http\Controllers\InvoiceController::class);
    Route::post('/invoices/{invoice}/generate-pdf', [\App\Http\Controllers\InvoiceController::class, 'generatePdf'])->name('invoices.generate-pdf');
    
    // Payments
    Route::resource('payments', \App\Http\Controllers\PaymentController::class);
    
    // Payment Responsibilities
    Route::resource('payment-responsibilities', \App\Http\Controllers\PaymentResponsibilityController::class);
});
