<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BusinessController;
use App\Http\Controllers\Api\AuthController as ApiAuthController;
use App\Http\Controllers\Api\ClientVerificationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/


// Public API routes (no authentication required for registration)
Route::prefix('v1')->group(function () {
    // Business and User Registration
    Route::post('/businesses/register', [BusinessController::class, 'register'])->name('api.businesses.register');
    
    // Check if business exists
    Route::get('/businesses/check', [BusinessController::class, 'checkExists'])->name('api.businesses.check');
    
    // Check if user exists
    Route::get('/users/check', [BusinessController::class, 'checkUserExists'])->name('api.users.check');
    
    // Create user for existing business
    Route::post('/businesses/{id}/users', [BusinessController::class, 'createUser'])->name('api.businesses.users.create');
    
    // API Authentication
    Route::post('/auth/login', [ApiAuthController::class, 'login'])->name('api.auth.login');
    
    // Plan benefits (public for form)
    Route::get('/plans/{id}/benefits', [\App\Http\Controllers\PlanController::class, 'getBenefits'])->name('api.plans.benefits');
    
    // Password reset token generation (public)
    Route::post('/password/reset-token', [BusinessController::class, 'generatePasswordResetToken'])->name('api.password.reset-token');
    
    // Get insurance company by code (public, for client registration)
    Route::get('/businesses/by-code/{code}', [BusinessController::class, 'getByCode'])->name('api.businesses.by-code');
    
    // Create business connection (public, for client registration)
    Route::post('/businesses/connections', [BusinessController::class, 'createConnection'])->name('api.businesses.connections.create');
    
    // Get connected vendors for a business (public, for kashtre entities)
    Route::get('/businesses/{businessId}/connected-vendors', [BusinessController::class, 'getConnectedVendors'])->name('api.businesses.connected-vendors');
    
    // Verify policy number exists (public, for client registration)
    // Supports both GET (policy number only) and POST (with alternative verification data)
    Route::get('/policies/verify/{insuranceCompanyId}/{policyNumber}', [BusinessController::class, 'verifyPolicyNumber'])->name('api.policies.verify');
    Route::post('/policies/verify/{insuranceCompanyId}', [BusinessController::class, 'verifyPolicyNumber'])->name('api.policies.verify.post');
    
    // Visit-based identity verification
    Route::post('/policies/verify-visit/{insuranceCompanyId}', [BusinessController::class, 'verifyVisitIdentity'])->name('api.policies.verify-visit');
    
    // Client verification by phone number and OTP
    Route::post('/clients/search-and-send-otp', [ClientVerificationController::class, 'searchAndSendOtp'])->name('api.clients.search-and-send-otp');
    Route::post('/clients/verify-otp', [ClientVerificationController::class, 'verifyOtp'])->name('api.clients.verify-otp');
    
    // Client verification by email and OTP
    Route::post('/clients/search-and-send-otp-email', [ClientVerificationController::class, 'searchAndSendOtpByEmail'])->name('api.clients.search-and-send-otp-email');
    Route::post('/clients/verify-otp-email', [ClientVerificationController::class, 'verifyOtpByEmail'])->name('api.clients.verify-otp-email');
    
    // Protected routes (require API token)
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/businesses/{id}', [BusinessController::class, 'show'])->name('api.businesses.show');
        Route::get('/user', [ApiAuthController::class, 'user'])->name('api.auth.user');
    });
});
