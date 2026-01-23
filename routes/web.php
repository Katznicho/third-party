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
    
    // Password Reset Routes
    Route::get('/password/reset', [\App\Http\Controllers\Auth\PasswordResetController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/password/email', [\App\Http\Controllers\Auth\PasswordResetController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/password/reset/{token}', [\App\Http\Controllers\Auth\PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/password/reset', [\App\Http\Controllers\Auth\PasswordResetController::class, 'reset'])->name('password.update');
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
    
    // Connected Companies
    Route::get('/connected-companies', [\App\Http\Controllers\ConnectedCompaniesController::class, 'index'])->name('connected-companies.index');
});
