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
    Route::get('/clients/{client}/account-statement', [\App\Http\Controllers\ClientController::class, 'accountStatement'])->name('clients.account-statement');
    Route::post('/clients/{client}/check-mobile-money-payments', [\App\Http\Controllers\ClientController::class, 'checkMobileMoneyPayments'])->name('clients.check-mobile-money-payments');
    Route::get('/clients/{client}/pay-premium', [\App\Http\Controllers\PremiumPaymentController::class, 'showPayPremium'])->name('clients.pay-premium');
    Route::post('/clients/{client}/pay-premium', [\App\Http\Controllers\PremiumPaymentController::class, 'processPayPremium'])->name('clients.pay-premium.process');
    
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
    
    // Authorization Codes (invoice authorizations from Kashtre for third parties to track)
    Route::get('/authorization-codes', [\App\Http\Controllers\AuthorizationCodeController::class, 'index'])->name('authorization-codes.index');

    // Invoices
    Route::get('/invoices', [\App\Http\Controllers\InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/{invoiceId}', [\App\Http\Controllers\InvoiceController::class, 'show'])->name('invoices.show');
    Route::post('/invoices/{invoiceId}/mark-paid', [\App\Http\Controllers\InvoiceController::class, 'markAsPaid'])->name('invoices.mark-paid');
    Route::post('/invoices/bulk-pay', [\App\Http\Controllers\InvoiceController::class, 'bulkPay'])->name('invoices.bulk-pay');
    Route::post('/invoices/{invoice}/generate-pdf', [\App\Http\Controllers\InvoiceController::class, 'generatePdf'])->name('invoices.generate-pdf');
    
    // Payments (custom routes first so they are not matched as payments/{payment})
    Route::get('/payments/record-client-portion', [\App\Http\Controllers\PaymentController::class, 'recordClientPortionForm'])->name('payments.record-client-portion');
    Route::post('/payments/record-client-portion', [\App\Http\Controllers\PaymentController::class, 'storeRecordClientPortion'])->name('payments.record-client-portion.store');
    Route::resource('payments', \App\Http\Controllers\PaymentController::class);
    Route::post('/payments/{payment}/mark-received', [\App\Http\Controllers\PaymentController::class, 'markReceived'])->name('payments.mark-received');

    // Balance statement (alias for client account statement – same data as clients/{id}/account-statement)
    Route::get('/balance-statement/{client}', function (\App\Models\Client $client) {
        return redirect()->route('clients.account-statement', $client);
    })->name('balance-statement.show');

    // Third-party vendors (connected Kashtre businesses) – show vendor and their payments
    Route::get('/third-party-vendors/{id}', [\App\Http\Controllers\ThirdPartyVendorController::class, 'show'])->name('third-party-vendors.show');
    
    // Payment Responsibilities
    Route::resource('payment-responsibilities', \App\Http\Controllers\PaymentResponsibilityController::class);
    
    // Service Providers
    Route::get('/connected-companies', [\App\Http\Controllers\ConnectedCompaniesController::class, 'index'])->name('connected-companies.index');
    
    // Vendor Code Email
    Route::get('/vendor-code/send', [\App\Http\Controllers\VendorCodeController::class, 'create'])->name('vendor-code.create');
    Route::post('/vendor-code/send', [\App\Http\Controllers\VendorCodeController::class, 'send'])->name('vendor-code.send');
    
    // Medical Questions
    Route::resource('medical-questions', \App\Http\Controllers\MedicalQuestionController::class);
    Route::post('/medical-questions/update-order', [\App\Http\Controllers\MedicalQuestionController::class, 'updateOrder'])->name('medical-questions.update-order');
    
    // Settings
    Route::get('/settings', [\App\Http\Controllers\SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings/policy-number', [\App\Http\Controllers\SettingsController::class, 'updatePolicyNumberSettings'])->name('settings.update-policy-number');
    Route::put('/settings/deductible-contribution', [\App\Http\Controllers\SettingsController::class, 'updateDeductibleContributionSettings'])->name('settings.update-deductible-contribution');
    Route::put('/settings/required-client-fields', [\App\Http\Controllers\SettingsController::class, 'updateRequiredClientFields'])->name('settings.update-required-client-fields');
    Route::put('/settings/verification', [\App\Http\Controllers\SettingsController::class, 'updateVerificationSettings'])->name('settings.update-verification');
    Route::put('/settings/authorization', [\App\Http\Controllers\SettingsController::class, 'updateAuthorizationSettings'])->name('settings.update-authorization');
    Route::put('/settings/account-number', [\App\Http\Controllers\SettingsController::class, 'updateAccountNumberSettings'])->name('settings.update-account-number');
    Route::put('/settings/payment', [\App\Http\Controllers\SettingsController::class, 'updatePaymentSettings'])->name('settings.update-payment');
    
    // Coverage Decision Matrix
    Route::resource('settings/coverage-decision-matrix', \App\Http\Controllers\CoverageDecisionMatrixController::class)->names([
        'index' => 'settings.coverage-decision-matrix.index',
        'store' => 'settings.coverage-decision-matrix.store',
        'update' => 'settings.coverage-decision-matrix.update',
        'destroy' => 'settings.coverage-decision-matrix.destroy',
    ]);
    
    // Pre-Authorization Triggers
    Route::resource('settings/pre-authorization-triggers', \App\Http\Controllers\PreAuthorizationTriggerController::class)->names([
        'index' => 'settings.pre-authorization-triggers.index',
        'store' => 'settings.pre-authorization-triggers.store',
        'update' => 'settings.pre-authorization-triggers.update',
        'destroy' => 'settings.pre-authorization-triggers.destroy',
    ]);
    
    // Authorization Rules
    Route::resource('authorization-rules', \App\Http\Controllers\AuthorizationRuleController::class);
    
    // Authorization Review
    Route::prefix('authorization-review')->name('authorization-review.')->group(function () {
        Route::get('/', [\App\Http\Controllers\AuthorizationReviewController::class, 'index'])->name('index');
        Route::get('/{preAuthorization}', [\App\Http\Controllers\AuthorizationReviewController::class, 'show'])->name('show');
        Route::post('/{preAuthorization}/approve', [\App\Http\Controllers\AuthorizationReviewController::class, 'approve'])->name('approve');
        Route::post('/{preAuthorization}/reject', [\App\Http\Controllers\AuthorizationReviewController::class, 'reject'])->name('reject');
        Route::post('/{preAuthorization}/reprocess', [\App\Http\Controllers\AuthorizationReviewController::class, 'reprocess'])->name('reprocess');
    });
});
