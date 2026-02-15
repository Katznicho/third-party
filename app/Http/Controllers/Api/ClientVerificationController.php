<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\VerificationOtp;
use App\Mail\ClientOtpEmail;
use App\Services\MarzSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ClientVerificationController extends Controller
{
    protected $smsService;

    public function __construct(MarzSmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Search for a client by phone number and send OTP
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchAndSendOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|max:20',
        ]);

        $phone = $this->normalizePhoneNumber($request->phone);

        Log::info('ClientVerification: Searching client by phone', [
            'phone' => $phone,
            'original_phone' => $request->phone,
        ]);

        // Search for client by cell_phone
        $client = Client::where('cell_phone', $phone)
            ->orWhere('cell_phone', $this->formatPhoneNumber($phone))
            ->orWhere('cell_phone', 'like', '%' . substr($phone, -9) . '%') // Last 9 digits
            ->first();

        if (!$client) {
            Log::info('ClientVerification: Client not found', [
                'phone' => $phone,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Client not found with the provided phone number.',
                'data' => null,
            ], 404);
        }

        // Generate 6-digit OTP
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Invalidate any existing OTPs for this client and phone
        VerificationOtp::where('client_id', $client->id)
            ->where('verification_type', 'phone')
            ->where('identifier', $phone)
            ->whereNull('verified_at')
            ->delete();

        // Store OTP in database with 10 minutes expiration
        $verificationOtp = VerificationOtp::create([
            'client_id' => $client->id,
            'verification_type' => 'phone',
            'identifier' => $phone,
            'otp' => $otp,
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
            'ip_address' => $request->ip(),
        ]);

        // Prepare SMS message
        $message = "Your verification code is: {$otp}. This code will expire in 10 minutes. Do not share this code with anyone.";

        Log::info('ClientVerification: Sending OTP SMS', [
            'client_id' => $client->id,
            'phone' => $phone,
            'verification_otp_id' => $verificationOtp->id,
        ]);

        // Send OTP via SMS
        $smsResult = $this->smsService->sendSms($phone, $message);

        if (!($smsResult['success'] ?? false)) {
            Log::error('ClientVerification: Failed to send OTP SMS', [
                'client_id' => $client->id,
                'phone' => $phone,
                'sms_error' => $smsResult['message'] ?? 'Unknown error',
            ]);

            // Still return success but indicate SMS might have failed
            return response()->json([
                'success' => true,
                'message' => 'Client found, but failed to send OTP. Please try again.',
                'data' => [
                    'client_id' => $client->id,
                    'client_name' => $client->full_name,
                    'phone' => $phone,
                    'sms_sent' => false,
                    'sms_error' => $smsResult['message'] ?? 'Failed to send SMS',
                ],
            ], 200);
        }

        Log::info('ClientVerification: OTP sent successfully', [
            'client_id' => $client->id,
            'phone' => $phone,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'OTP has been sent to your phone number.',
            'data' => [
                'client_id' => $client->id,
                'client_name' => $client->full_name,
                'phone' => $phone,
                'sms_sent' => true,
                'otp_expires_in' => 600, // 10 minutes in seconds
            ],
        ], 200);
    }

    /**
     * Verify OTP for client
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|max:20',
            'otp' => 'required|string|size:6',
        ]);

        $phone = $this->normalizePhoneNumber($request->phone);
        $otp = $request->otp;

        Log::info('ClientVerification: Verifying OTP', [
            'phone' => $phone,
            'otp_provided' => $otp,
        ]);

        // Find client first
        $client = Client::where('cell_phone', $phone)
            ->orWhere('cell_phone', $this->formatPhoneNumber($phone))
            ->orWhere('cell_phone', 'like', '%' . substr($phone, -9) . '%')
            ->first();

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Client not found.',
            ], 404);
        }

        // Find valid OTP record
        $verificationOtp = VerificationOtp::where('client_id', $client->id)
            ->where('verification_type', 'phone')
            ->where('identifier', $phone)
            ->whereNull('verified_at')
            ->valid()
            ->latest()
            ->first();

        if (!$verificationOtp) {
            Log::warning('ClientVerification: OTP not found or expired', [
                'client_id' => $client->id,
                'phone' => $phone,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'OTP not found or has expired. Please request a new OTP.',
            ], 400);
        }

        // Check attempts (max 5 attempts)
        if ($verificationOtp->hasExceededMaxAttempts()) {
            Log::warning('ClientVerification: Too many OTP verification attempts', [
                'client_id' => $client->id,
                'phone' => $phone,
                'attempts' => $verificationOtp->attempts,
            ]);

            $verificationOtp->delete();

            return response()->json([
                'success' => false,
                'message' => 'Too many failed attempts. Please request a new OTP.',
            ], 429);
        }

        // Verify OTP
        if ($verificationOtp->otp !== $otp) {
            // Increment attempts
            $verificationOtp->incrementAttempts();

            Log::warning('ClientVerification: Invalid OTP', [
                'client_id' => $client->id,
                'phone' => $phone,
                'attempts' => $verificationOtp->attempts,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP. ' . (5 - $verificationOtp->attempts) . ' attempts remaining.',
                'attempts_remaining' => 5 - $verificationOtp->attempts,
            ], 400);
        }

        // OTP is valid - mark as verified
        $verificationOtp->markAsVerified();

        Log::info('ClientVerification: OTP verified successfully', [
            'client_id' => $client->id,
            'phone' => $phone,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'OTP verified successfully.',
            'data' => [
                'client_id' => $client->id,
                'client_name' => $client->full_name,
                'phone' => $phone,
                'verified' => true,
            ],
        ], 200);
    }

    /**
     * Normalize phone number to international format
     * 
     * @param string $phone
     * @return string
     */
    protected function normalizePhoneNumber(string $phone): string
    {
        // Remove all non-digit characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // If starts with 0, replace with +256
        if (strpos($phone, '0') === 0) {
            $phone = '+256' . substr($phone, 1);
        } elseif (strpos($phone, '256') === 0) {
            $phone = '+' . $phone;
        } elseif (strpos($phone, '+256') !== 0) {
            $phone = '+256' . $phone;
        }

        return $phone;
    }

    /**
     * Format phone number for database search (without +)
     * 
     * @param string $phone
     * @return string
     */
    protected function formatPhoneNumber(string $phone): string
    {
        return str_replace('+', '', $phone);
    }

    /**
     * Search for a client by email and send OTP
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchAndSendOtpByEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $email = strtolower(trim($request->email));

        Log::info('ClientVerification: Searching client by email', [
            'email' => $email,
        ]);

        // Search for client by email
        $client = Client::where('email', $email)
            ->orWhere('email', 'like', '%' . $email . '%')
            ->first();

        if (!$client) {
            Log::info('ClientVerification: Client not found by email', [
                'email' => $email,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Client not found with the provided email address.',
                'data' => null,
            ], 404);
        }

        // Generate 6-digit OTP
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Invalidate any existing OTPs for this client and email
        VerificationOtp::where('client_id', $client->id)
            ->where('verification_type', 'email')
            ->where('identifier', $email)
            ->whereNull('verified_at')
            ->delete();

        // Store OTP in database with 10 minutes expiration
        $verificationOtp = VerificationOtp::create([
            'client_id' => $client->id,
            'verification_type' => 'email',
            'identifier' => $email,
            'otp' => $otp,
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
            'ip_address' => $request->ip(),
        ]);

        Log::info('ClientVerification: Sending OTP email', [
            'client_id' => $client->id,
            'email' => $email,
            'verification_otp_id' => $verificationOtp->id,
        ]);

        try {
            // Send OTP via Email
            Mail::to($email)->send(
                new ClientOtpEmail(
                    $otp,
                    $client->full_name,
                    10 // expires in 10 minutes
                )
            );

            Log::info('ClientVerification: OTP email sent successfully', [
                'client_id' => $client->id,
                'email' => $email,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'OTP has been sent to your email address.',
                'data' => [
                    'client_id' => $client->id,
                    'client_name' => $client->full_name,
                    'email' => $email,
                    'email_sent' => true,
                    'otp_expires_in' => 600, // 10 minutes in seconds
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('ClientVerification: Failed to send OTP email', [
                'client_id' => $client->id,
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Client found, but failed to send OTP email. Please try again.',
                'data' => [
                    'client_id' => $client->id,
                    'client_name' => $client->full_name,
                    'email' => $email,
                    'email_sent' => false,
                    'email_error' => $e->getMessage(),
                ],
            ], 200);
        }
    }

    /**
     * Verify OTP for client by email
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyOtpByEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
            'otp' => 'required|string|size:6',
        ]);

        $email = strtolower(trim($request->email));
        $otp = $request->otp;

        Log::info('ClientVerification: Verifying OTP by email', [
            'email' => $email,
            'otp_provided' => $otp,
        ]);

        // Find client first
        $client = Client::where('email', $email)
            ->orWhere('email', 'like', '%' . $email . '%')
            ->first();

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Client not found.',
            ], 404);
        }

        // Find valid OTP record
        $verificationOtp = VerificationOtp::where('client_id', $client->id)
            ->where('verification_type', 'email')
            ->where('identifier', $email)
            ->whereNull('verified_at')
            ->valid()
            ->latest()
            ->first();

        if (!$verificationOtp) {
            Log::warning('ClientVerification: OTP not found or expired (email)', [
                'client_id' => $client->id,
                'email' => $email,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'OTP not found or has expired. Please request a new OTP.',
            ], 400);
        }

        // Check attempts (max 5 attempts)
        if ($verificationOtp->hasExceededMaxAttempts()) {
            Log::warning('ClientVerification: Too many OTP verification attempts (email)', [
                'client_id' => $client->id,
                'email' => $email,
                'attempts' => $verificationOtp->attempts,
            ]);

            $verificationOtp->delete();

            return response()->json([
                'success' => false,
                'message' => 'Too many failed attempts. Please request a new OTP.',
            ], 429);
        }

        // Verify OTP
        if ($verificationOtp->otp !== $otp) {
            // Increment attempts
            $verificationOtp->incrementAttempts();

            Log::warning('ClientVerification: Invalid OTP (email)', [
                'client_id' => $client->id,
                'email' => $email,
                'attempts' => $verificationOtp->attempts,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP. ' . (5 - $verificationOtp->attempts) . ' attempts remaining.',
                'attempts_remaining' => 5 - $verificationOtp->attempts,
            ], 400);
        }

        // OTP is valid - mark as verified
        $verificationOtp->markAsVerified();

        Log::info('ClientVerification: OTP verified successfully (email)', [
            'client_id' => $client->id,
            'email' => $email,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'OTP verified successfully.',
            'data' => [
                'client_id' => $client->id,
                'client_name' => $client->full_name,
                'email' => $email,
                'verified' => true,
            ],
        ], 200);
    }
}
