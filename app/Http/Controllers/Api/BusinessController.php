<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InsuranceCompany;
use App\Models\User;
use App\Models\BusinessConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BusinessController extends Controller
{
    /**
     * Register a new business (insurance company) and user
     * 
     * This endpoint creates an insurance company and an associated user account
     * that can be used to access the third-party system.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                // Business/Insurance Company fields
                'name' => 'required|string|max:255',
                'code' => ['nullable', 'string', 'size:8', 'regex:/^[A-Z0-9]{8}$/', 'unique:insurance_companies,code'],
                'email' => 'required|email|max:255',
                'phone' => 'nullable|string|max:255',
                'address' => 'nullable|string',
                'head_office_address' => 'nullable|string',
                'postal_address' => 'nullable|string',
                'website' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                
                // User fields
                'user_name' => 'required|string|max:255',
                'user_email' => 'required|email|max:255|unique:users,email',
                'user_username' => 'required|string|max:255|unique:users,username',
                'user_password' => 'required|string|min:8',
                
                // Connection field (optional)
                'connect_to_insurance_company_id' => 'nullable|exists:insurance_companies,id',
            ], [
                'code.size' => 'The company code must be exactly 8 characters.',
                'code.regex' => 'The company code must contain only uppercase letters and numbers (8 characters).',
                'code.unique' => 'This company code is already in use.',
                'connect_to_insurance_company_id.exists' => 'The insurance company to connect to does not exist.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $validated = $validator->validated();

            // Generate 8-character alphanumeric code if not provided
            if (empty($validated['code'])) {
                do {
                    // Generate random 8-character alphanumeric code (uppercase letters and numbers)
                    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
                    $code = '';
                    for ($i = 0; $i < 8; $i++) {
                        $code .= $characters[rand(0, strlen($characters) - 1)];
                    }
                } while (InsuranceCompany::where('code', $code)->exists());
            } else {
                // Ensure code is uppercase
                $code = strtoupper($validated['code']);
            }
            
            $slug = Str::slug($validated['name']);

            // Check if slug already exists, if so append random string
            if (InsuranceCompany::where('slug', $slug)->exists()) {
                $slug = $slug . '-' . Str::random(4);
            }

            // Create Insurance Company (Business)
            // Use head_office_address if provided, otherwise fall back to address
            $headOfficeAddress = $validated['head_office_address'] ?? $validated['address'] ?? null;
            
            $insuranceCompany = InsuranceCompany::create([
                'name' => $validated['name'],
                'code' => $code,
                'slug' => $slug,
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'head_office_address' => $headOfficeAddress,
                'postal_address' => $validated['postal_address'] ?? null,
                'website' => $validated['website'] ?? null,
                'description' => $validated['description'] ?? null,
                'is_active' => true,
            ]);

            // Create connection if connect_to_insurance_company_id is provided
            if (!empty($validated['connect_to_insurance_company_id'])) {
                $connectToId = $validated['connect_to_insurance_company_id'];
                
                // Verify the insurance company exists
                $connectToCompany = InsuranceCompany::find($connectToId);
                if ($connectToCompany) {
                    // Create bidirectional connection
                    DB::table('business_connections')->insertOrIgnore([
                        'insurance_company_id' => $connectToId,
                        'connected_business_id' => $insuranceCompany->id,
                        'connection_type' => 'client',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    
                    \Illuminate\Support\Facades\Log::info('Business connection created', [
                        'insurance_company_id' => $connectToId,
                        'connected_business_id' => $insuranceCompany->id,
                    ]);
                }
            }

            // Create User for the Insurance Company
            $user = User::create([
                'name' => $validated['user_name'],
                'username' => $validated['user_username'],
                'email' => $validated['user_email'],
                'password' => Hash::make('password'),
                'insurance_company_id' => $insuranceCompany->id,
            ]);

            // Generate API token for the user
            $token = $user->createToken('api-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Business and user registered successfully',
                'data' => [
                    'business' => [
                        'id' => $insuranceCompany->id,
                        'name' => $insuranceCompany->name,
                        'code' => $insuranceCompany->code,
                        'slug' => $insuranceCompany->slug,
                        'email' => $insuranceCompany->email,
                        'phone' => $insuranceCompany->phone,
                        'head_office_address' => $insuranceCompany->head_office_address,
                        'postal_address' => $insuranceCompany->postal_address,
                        'website' => $insuranceCompany->website,
                        'description' => $insuranceCompany->description,
                        'is_active' => $insuranceCompany->is_active,
                    ],
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'username' => $user->username,
                        'email' => $user->email,
                    ],
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                ],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to register business and user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check if business exists by name or email
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkExists(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
        ]);

        $insuranceCompany = InsuranceCompany::where('name', $request->name)
            ->orWhere('email', $request->email)
            ->first();

        if ($insuranceCompany) {
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $insuranceCompany->id,
                    'name' => $insuranceCompany->name,
                    'code' => $insuranceCompany->code,
                    'slug' => $insuranceCompany->slug,
                    'email' => $insuranceCompany->email,
                    'phone' => $insuranceCompany->phone,
                    'head_office_address' => $insuranceCompany->head_office_address,
                    'postal_address' => $insuranceCompany->postal_address,
                    'website' => $insuranceCompany->website,
                    'description' => $insuranceCompany->description,
                    'is_active' => $insuranceCompany->is_active,
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Business not found.',
            'data' => null,
        ], 404);
    }

    /**
     * Create user for existing business
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function createUser(Request $request, $id)
    {
        $insuranceCompany = InsuranceCompany::find($id);

        if (!$insuranceCompany) {
            return response()->json([
                'success' => false,
                'message' => 'Business not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'user_name' => 'required|string|max:255',
            'user_email' => 'required|email|max:255|unique:users,email',
            'user_username' => 'required|string|max:255|unique:users,username',
            'user_password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        try {
            // Create User for the Insurance Company
            $user = User::create([
                'name' => $validated['user_name'],
                'username' => $validated['user_username'],
                'email' => $validated['user_email'],
                'password' => Hash::make($validated['user_password']),
                'insurance_company_id' => $insuranceCompany->id,
            ]);

            // Generate API token for the user
            $token = $user->createToken('api-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'User created successfully for existing business',
                'data' => [
                    'business' => [
                        'id' => $insuranceCompany->id,
                        'name' => $insuranceCompany->name,
                        'code' => $insuranceCompany->code,
                    ],
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'username' => $user->username,
                        'email' => $user->email,
                    ],
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check if user exists by email or username
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkUserExists(Request $request)
    {
        $request->validate([
            'email' => 'nullable|email',
            'username' => 'nullable|string',
        ]);

        $email = $request->input('email');
        $username = $request->input('username');

        if (!$email && !$username) {
            return response()->json([
                'success' => false,
                'message' => 'Either email or username is required.',
            ], 422);
        }

        $query = User::query();

        if ($email) {
            $query->orWhere('email', $email);
        }
        if ($username) {
            $query->orWhere('username', $username);
        }

        $user = $query->first();

        if ($user) {
            return response()->json([
                'success' => true,
                'exists' => true,
                'data' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'username' => $user->username,
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'exists' => false,
            'data' => null,
        ]);
    }

    /**
     * Get business details
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $insuranceCompany = InsuranceCompany::find($id);

        if (!$insuranceCompany) {
            return response()->json([
                'success' => false,
                'message' => 'Business not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $insuranceCompany->id,
                'name' => $insuranceCompany->name,
                'code' => $insuranceCompany->code,
                'slug' => $insuranceCompany->slug,
                'email' => $insuranceCompany->email,
                'phone' => $insuranceCompany->phone,
                'address' => $insuranceCompany->head_office_address,
                'description' => $insuranceCompany->description,
                'is_active' => $insuranceCompany->is_active,
            ],
        ]);
    }

    /**
     * Generate password reset token for a user and send email
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generatePasswordResetToken(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        // Generate password reset token
        $token = \Illuminate\Support\Str::random(64);
        
        // Store token in password_reset_tokens table
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );

        // Send password reset email from the third-party system
        \Illuminate\Support\Facades\Log::info('Attempting to send password reset email', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_name' => $user->name,
            'username' => $user->username,
            'token_generated' => true,
        ]);
        
        try {
            $user->sendPasswordResetNotification($token);
            
            \Illuminate\Support\Facades\Log::info('Password reset email sent successfully', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'reset_url' => url('/password/reset/' . $token . '?email=' . urlencode($user->email)),
                'mail_driver' => config('mail.default'),
                'from_address' => config('mail.from.address'),
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send password reset email', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send password reset email',
                'error' => $e->getMessage(),
            ], 500);
        }

        // Return reset URL (for reference, but email is already sent)
        $resetUrl = url('/password/reset/' . $token . '?email=' . urlencode($user->email));

        return response()->json([
            'success' => true,
            'message' => 'Password reset email sent successfully',
            'data' => [
                'email' => $user->email,
                'reset_url' => $resetUrl,
                'expires_in' => 60, // minutes
            ],
        ]);
    }

    /**
     * Get insurance company by code
     *
     * @param string $code 8-character alphanumeric insurance company code
     * @return \Illuminate\Http\JsonResponse
     */
    public function getByCode(string $code)
    {
        try {
            // Normalize code to uppercase
            $code = strtoupper($code);
            
            // Validate code format (8-character alphanumeric)
            if (!preg_match('/^[A-Z0-9]{8}$/', $code)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid code format. Code must be exactly 8 alphanumeric characters (uppercase letters and numbers).',
                ], 422);
            }

            $insuranceCompany = InsuranceCompany::where('code', $code)
                ->where('is_active', true)
                ->first();

            if (!$insuranceCompany) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insurance company not found with code: ' . $code,
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Insurance company found',
                'data' => [
                    'business' => [
                        'id' => $insuranceCompany->id,
                        'name' => $insuranceCompany->name,
                        'code' => $insuranceCompany->code,
                        'slug' => $insuranceCompany->slug,
                        'email' => $insuranceCompany->email,
                        'phone' => $insuranceCompany->phone,
                        'head_office_address' => $insuranceCompany->head_office_address,
                        'postal_address' => $insuranceCompany->postal_address,
                        'website' => $insuranceCompany->website,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to get insurance company by code', [
                'code' => $code,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving the insurance company',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a business connection
     * 
     * Connects an existing business to an insurance company
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createConnection(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'insurance_company_id' => 'required|exists:insurance_companies,id',
                'connected_business_id' => 'required|integer', // Kashtre business ID, doesn't need to exist in third-party
                'connected_business_name' => 'nullable|string|max:255', // Kashtre business name
            ], [
                'insurance_company_id.exists' => 'The insurance company does not exist.',
                'connected_business_id.required' => 'The connected business ID is required.',
                'connected_business_id.integer' => 'The connected business ID must be an integer.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $validated = $validator->validated();

            // Check if connection already exists
            $existingConnection = DB::table('business_connections')
                ->where('insurance_company_id', $validated['insurance_company_id'])
                ->where('connected_business_id', $validated['connected_business_id'])
                ->first();

            if ($existingConnection) {
                return response()->json([
                    'success' => true,
                    'message' => 'Connection already exists',
                    'data' => [
                        'connection_id' => $existingConnection->id,
                    ],
                ]);
            }

            // Create connection
            $connectionId = DB::table('business_connections')->insertGetId([
                'insurance_company_id' => $validated['insurance_company_id'],
                'connected_business_id' => $validated['connected_business_id'],
                'connected_business_name' => $validated['connected_business_name'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            \Illuminate\Support\Facades\Log::info('Business connection created via API', [
                'insurance_company_id' => $validated['insurance_company_id'],
                'connected_business_id' => $validated['connected_business_id'],
                'connection_id' => $connectionId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Business connection created successfully',
                'data' => [
                    'connection_id' => $connectionId,
                ],
            ], 201);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to create business connection', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create business connection',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get connected vendors (insurance companies) for a kashtre business
     *
     * @param int $businessId The kashtre business ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function getConnectedVendors($businessId)
    {
        try {
            // Find all connections where connected_business_id matches the kashtre business ID
            $connections = BusinessConnection::where('connected_business_id', $businessId)
                ->with('insuranceCompany')
                ->get();

            $vendors = $connections->map(function ($connection) {
                $company = $connection->insuranceCompany;
                if (!$company) {
                    return null;
                }
                return [
                    'id' => $company->id,
                    'name' => $company->name,
                    'code' => $company->code,
                    'email' => $company->email,
                    'phone' => $company->phone,
                    'is_active' => $company->is_active,
                    'connected_at' => $connection->created_at->toDateTimeString(),
                ];
            })->filter();

            return response()->json([
                'success' => true,
                'message' => 'Connected vendors retrieved successfully',
                'data' => $vendors->values(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve connected vendors',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify if a policy number exists for a given insurance company
     * Supports alternative verification methods when policy number fails
     */
    public function verifyPolicyNumber(Request $request, $insuranceCompanyId, $policyNumber = null)
    {
        try {
            $insuranceCompany = InsuranceCompany::findOrFail($insuranceCompanyId);
            
            // Get policy number from route parameter or request
            $policyNumber = $policyNumber ?? $request->input('policy_number');
            
            // If policy number is provided, try primary verification first
            if ($policyNumber) {
                $policy = \App\Models\Policy::where('insurance_company_id', $insuranceCompanyId)
                    ->where('policy_number', $policyNumber)
                    ->where('status', 'active')
                    ->with(['principalMember', 'insuranceCompany'])
                    ->first();

                if ($policy) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Policy number verified',
                        'exists' => true,
                        'verification_method' => 'policy_number',
                        'data' => [
                            'policy_number' => $policy->policy_number,
                            'insurance_company_id' => $policy->insurance_company_id,
                            'insurance_company_name' => $policy->insuranceCompany->name ?? null,
                            'principal_member_id' => $policy->principal_member_id,
                            'principal_member_name' => $policy->principalMember ? ($policy->principalMember->first_name . ' ' . $policy->principalMember->surname) : null,
                            'status' => $policy->status,
                            'expiry_date' => $policy->expiry_date?->toDateString(),
                        ],
                    ], 200);
                }
            }

            // Policy number verification failed, try alternative methods
            $alternativeData = $request->only([
                'name', 'date_of_birth', 'id_passport_no', 'phone', 'email', 'visit_id'
            ]);

            // Check if any alternative verification is enabled
            if (empty($alternativeData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Policy number not found or inactive. Please provide alternative verification information.',
                    'exists' => false,
                    'requires_alternative_verification' => true,
                ], 404);
            }

            // Try alternative verification methods
            $verificationResult = $this->attemptAlternativeVerification($insuranceCompany, $alternativeData);

            if ($verificationResult['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $verificationResult['message'],
                    'exists' => true,
                    'verification_method' => $verificationResult['method'],
                    'verification_status' => $verificationResult['status'],
                    'data' => $verificationResult['data'],
                    'warnings' => $verificationResult['warnings'] ?? [],
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => $verificationResult['message'],
                'exists' => false,
                'verification_status' => $verificationResult['status'] ?? 'rejected',
                'mismatches' => $verificationResult['mismatches'] ?? [],
            ], 404);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to verify policy number', [
                'insurance_company_id' => $insuranceCompanyId,
                'policy_number' => $policyNumber,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to verify policy number',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Attempt alternative verification methods
     */
    private function attemptAlternativeVerification(InsuranceCompany $insuranceCompany, array $data)
    {
        $mismatches = [];
        $warnings = [];
        $matchedPolicy = null;
        $matchedClient = null;
        $verificationMethod = null;
        $attemptLog = [];

        \Illuminate\Support\Facades\Log::info('=== ALTERNATIVE VERIFICATION START ===', [
            'insurance_company_id' => $insuranceCompany->id,
            'insurance_company_name' => $insuranceCompany->name,
            'provided_data' => [
                'has_visit_id' => !empty($data['visit_id']),
                'has_name' => !empty($data['name']),
                'has_date_of_birth' => !empty($data['date_of_birth']),
                'has_id_passport_no' => !empty($data['id_passport_no']),
                'has_phone' => !empty($data['phone']),
                'has_email' => !empty($data['email']),
            ],
            'enabled_methods' => [
                'visit_verification' => $insuranceCompany->enable_visit_verification ?? false,
                'name_dob_verification' => $insuranceCompany->enable_name_dob_verification ?? false,
                'id_passport_verification' => $insuranceCompany->enable_id_passport_verification ?? false,
                'phone_verification' => $insuranceCompany->enable_phone_verification ?? false,
                'email_verification' => $insuranceCompany->enable_email_verification ?? false,
            ],
        ]);

        // Try visit-based verification first (if enabled and visit_id provided)
        if ($insuranceCompany->enable_visit_verification && !empty($data['visit_id'])) {
            \Illuminate\Support\Facades\Log::info('🔍 Attempting Visit ID verification', [
                'visit_id' => $data['visit_id'],
                'insurance_company_id' => $insuranceCompany->id,
            ]);
            
            $visitVerification = \App\Models\VisitIdentityVerification::where('visit_id', $data['visit_id'])
                ->where('insurance_company_id', $insuranceCompany->id)
                ->where('verification_status', 'verified')
                ->where('expires_at', '>', now())
                ->with(['policy', 'client'])
                ->first();

            if ($visitVerification && $visitVerification->policy) {
                // Check the action setting for visit verification
                $action = $insuranceCompany->visit_verification_action ?? 'auto_accept';
                $status = 'verified';
                
                if ($action === 'auto_reject') {
                    \Illuminate\Support\Facades\Log::info('❌ Visit ID verification REJECTED by settings', [
                        'visit_id' => $data['visit_id'],
                        'policy_number' => $visitVerification->policy->policy_number,
                    ]);
                    
                    return [
                        'success' => false,
                        'message' => 'Verification rejected based on insurance company settings.',
                        'status' => 'rejected',
                    ];
                } elseif ($action === 'flag_for_review') {
                    $status = 'flagged';
                }
                
                \Illuminate\Support\Facades\Log::info('✅ Visit ID verification SUCCESS', [
                    'visit_id' => $data['visit_id'],
                    'policy_number' => $visitVerification->policy->policy_number,
                    'status' => $status,
                ]);
                
                return [
                    'success' => true,
                    'message' => 'Client verified using visit ID',
                    'method' => 'visit_id',
                    'status' => $status,
                    'data' => [
                        'policy_number' => $visitVerification->policy->policy_number,
                        'insurance_company_id' => $visitVerification->policy->insurance_company_id,
                        'principal_member_id' => $visitVerification->policy->principal_member_id,
                        'visit_id' => $visitVerification->visit_id,
                        'verified_at' => $visitVerification->verified_at?->toIso8601String(),
                    ],
                ];
            } else {
                \Illuminate\Support\Facades\Log::info('❌ Visit ID verification FAILED', [
                    'visit_id' => $data['visit_id'],
                    'reason' => $visitVerification ? 'Visit expired or not verified' : 'No visit verification found',
                ]);
                $attemptLog[] = 'Visit ID: No matching verified visit found';
            }
        } elseif ($insuranceCompany->enable_visit_verification && empty($data['visit_id'])) {
            $attemptLog[] = 'Visit ID: Method enabled but no visit_id provided';
        }

        // Try name + date of birth verification
        if ($insuranceCompany->enable_name_dob_verification && !empty($data['name']) && !empty($data['date_of_birth'])) {
            \Illuminate\Support\Facades\Log::info('🔍 Attempting Name & DOB verification', [
                'provided_name' => $data['name'],
                'provided_dob' => $data['date_of_birth'],
                'name_similarity_threshold' => $insuranceCompany->name_similarity_threshold,
                'dob_tolerance_days' => $insuranceCompany->dob_tolerance_days,
            ]);
            
            $policies = \App\Models\Policy::where('insurance_company_id', $insuranceCompany->id)
                ->where('status', 'active')
                ->with(['principalMember'])
                ->get();

            \Illuminate\Support\Facades\Log::info('📋 Checking against policies', [
                'total_policies' => $policies->count(),
                'name_similarity_threshold' => $insuranceCompany->name_similarity_threshold,
                'dob_tolerance_days' => $insuranceCompany->dob_tolerance_days,
            ]);

            $bestMatch = null;
            $bestSimilarity = 0;
            $closestDobDiff = null;

            foreach ($policies as $policy) {
                if ($policy->principalMember) {
                    $clientName = trim(($policy->principalMember->first_name ?? '') . ' ' . ($policy->principalMember->surname ?? ''));
                    $providedName = trim($data['name']);
                    $similarity = $this->calculateNameSimilarity($clientName, $providedName);
                    
                    $dobMatch = false;
                    $daysDiff = null;
                    if ($policy->principalMember->date_of_birth && $data['date_of_birth']) {
                        $clientDob = \Carbon\Carbon::parse($policy->principalMember->date_of_birth);
                        $providedDob = \Carbon\Carbon::parse($data['date_of_birth']);
                        $daysDiff = abs($clientDob->diffInDays($providedDob));
                        $dobMatch = $daysDiff <= ($insuranceCompany->dob_tolerance_days ?? 0);
                    }

                    // Track best match for logging
                    if ($similarity > $bestSimilarity) {
                        $bestSimilarity = $similarity;
                        $bestMatch = [
                            'policy_number' => $policy->policy_number,
                            'client_name' => $clientName,
                            'similarity' => $similarity,
                            'days_diff' => $daysDiff,
                        ];
                    }
                    if ($daysDiff !== null && ($closestDobDiff === null || $daysDiff < $closestDobDiff)) {
                        $closestDobDiff = $daysDiff;
                    }

                    \Illuminate\Support\Facades\Log::info('🔎 Comparing with policy', [
                        'policy_number' => $policy->policy_number,
                        'client_name' => $clientName,
                        'provided_name' => $providedName,
                        'name_similarity' => $similarity,
                        'name_threshold' => $insuranceCompany->name_similarity_threshold,
                        'name_match' => $similarity >= $insuranceCompany->name_similarity_threshold,
                        'client_dob' => $policy->principalMember->date_of_birth?->toDateString(),
                        'provided_dob' => $data['date_of_birth'],
                        'days_diff' => $daysDiff,
                        'dob_match' => $dobMatch,
                        'dob_tolerance_days' => $insuranceCompany->dob_tolerance_days,
                    ]);

                    if ($similarity >= $insuranceCompany->name_similarity_threshold && $dobMatch) {
                        $matchedPolicy = $policy;
                        $matchedClient = $policy->principalMember;
                        $verificationMethod = 'name_dob';
                        
                        \Illuminate\Support\Facades\Log::info('✅ Name & DOB verification SUCCESS', [
                            'policy_number' => $policy->policy_number,
                            'name_similarity' => $similarity,
                        ]);
                        
                        if ($similarity < 100) {
                            $warnings[] = "Name similarity: {$similarity}% (threshold: {$insuranceCompany->name_similarity_threshold}%)";
                        }
                        break;
                    } elseif ($similarity >= $insuranceCompany->name_similarity_threshold && !$dobMatch) {
                        $mismatches[] = 'date_of_birth';
                        \Illuminate\Support\Facades\Log::info('⚠️ Name matched but DOB did not', [
                            'policy_number' => $policy->policy_number,
                            'name_similarity' => $similarity,
                        ]);
                    } elseif ($similarity < $insuranceCompany->name_similarity_threshold && $dobMatch) {
                        $mismatches[] = 'name';
                        \Illuminate\Support\Facades\Log::info('⚠️ DOB matched but name did not', [
                            'policy_number' => $policy->policy_number,
                            'name_similarity' => $similarity,
                        ]);
                    }
                }
            }
            
            if (!$matchedPolicy) {
                $failureReason = 'No policy matched the name and DOB criteria';
                $details = [];
                
                if ($bestMatch) {
                    $details[] = 'Best name match: ' . $bestMatch['client_name'] . ' (' . $bestMatch['similarity'] . '% similarity, threshold: ' . $insuranceCompany->name_similarity_threshold . '%)';
                }
                if ($closestDobDiff !== null) {
                    $details[] = 'Closest DOB difference: ' . $closestDobDiff . ' days (tolerance: ' . ($insuranceCompany->dob_tolerance_days ?? 0) . ' days)';
                }
                
                \Illuminate\Support\Facades\Log::info('❌ Name & DOB verification FAILED', [
                    'reason' => $failureReason,
                    'policies_checked' => $policies->count(),
                    'best_match' => $bestMatch,
                    'closest_dob_diff' => $closestDobDiff,
                    'details' => $details,
                ]);
                
                $logMessage = 'Name & Date of Birth: No matching policy found';
                if (!empty($details)) {
                    $logMessage .= ' (' . implode('; ', $details) . ')';
                }
                $attemptLog[] = $logMessage;
            }
        } elseif ($insuranceCompany->enable_name_dob_verification) {
            $missing = [];
            if (empty($data['name'])) $missing[] = 'name';
            if (empty($data['date_of_birth'])) $missing[] = 'date_of_birth';
            $attemptLog[] = 'Name & Date of Birth: Method enabled but missing ' . implode(' and ', $missing);
        }

        // Try ID/Passport verification
        if (!$matchedPolicy && $insuranceCompany->enable_id_passport_verification && !empty($data['id_passport_no'])) {
            \Illuminate\Support\Facades\Log::info('🔍 Attempting ID/Passport verification', [
                'provided_id_passport_no' => $data['id_passport_no'],
                'insurance_company_id' => $insuranceCompany->id,
            ]);
            
            // Find clients with matching ID/Passport that have policies with this insurance company
            $clients = \App\Models\Client::where('id_passport_no', $data['id_passport_no'])
                ->whereHas('policies', function($query) use ($insuranceCompany) {
                    $query->where('insurance_company_id', $insuranceCompany->id);
                })
                ->orWhereHas('principalMember.policies', function($query) use ($insuranceCompany) {
                    // Also check if this is a dependent whose principal has a policy
                    $query->where('insurance_company_id', $insuranceCompany->id);
                })
                ->get();
            
            \Illuminate\Support\Facades\Log::info('👤 Clients found with matching ID/Passport', [
                'total_clients' => $clients->count(),
                'clients' => $clients->map(function($c) {
                    return [
                        'client_id' => $c->id,
                        'client_name' => $c->first_name . ' ' . $c->surname,
                    ];
                })->toArray(),
            ]);
            
            foreach ($clients as $client) {
                $policy = \App\Models\Policy::where('insurance_company_id', $insuranceCompany->id)
                    ->where(function($query) use ($client) {
                        $query->where('principal_member_id', $client->id)
                              ->orWhereHas('dependents', function($q) use ($client) {
                                  $q->where('clients.id', $client->id);
                              });
                    })
                    ->where('status', 'active')
                    ->with(['principalMember'])
                    ->first();

                if ($policy) {
                    $matchedPolicy = $policy;
                    $matchedClient = $client;
                    $verificationMethod = 'id_passport';
                    
                    \Illuminate\Support\Facades\Log::info('✅ ID/Passport verification SUCCESS', [
                        'policy_number' => $policy->policy_number,
                        'client_id' => $client->id,
                    ]);
                    break;
                }
            }
            
            if (!$matchedPolicy) {
                if ($clients->count() > 0) {
                    \Illuminate\Support\Facades\Log::info('❌ ID/Passport verification FAILED', [
                        'reason' => 'Client(s) found but no active policy with this insurance company',
                        'clients_found' => $clients->count(),
                    ]);
                    $attemptLog[] = 'ID/Passport: Client found but no active policy';
                } else {
                    \Illuminate\Support\Facades\Log::info('❌ ID/Passport verification FAILED', [
                        'reason' => 'No client found with matching ID/Passport number for this insurance company',
                    ]);
                    $attemptLog[] = 'ID/Passport: No client found with matching ID/Passport';
                }
            }
        } elseif ($insuranceCompany->enable_id_passport_verification && empty($data['id_passport_no'])) {
            $attemptLog[] = 'ID/Passport: Method enabled but no id_passport_no provided';
        }

        // Try phone verification
        if (!$matchedPolicy && $insuranceCompany->enable_phone_verification && !empty($data['phone'])) {
            \Illuminate\Support\Facades\Log::info('🔍 Attempting Phone verification', [
                'provided_phone' => $data['phone'],
                'insurance_company_id' => $insuranceCompany->id,
            ]);
            
            // Find clients with matching phone that have policies with this insurance company
            $clients = \App\Models\Client::where(function($query) use ($data) {
                    $query->where('cell_phone', $data['phone'])
                          ->orWhere('whatsapp_line', $data['phone']);
                })
                ->whereHas('policies', function($query) use ($insuranceCompany) {
                    $query->where('insurance_company_id', $insuranceCompany->id);
                })
                ->orWhereHas('principalMember.policies', function($query) use ($insuranceCompany) {
                    // Also check if this is a dependent whose principal has a policy
                    $query->where('insurance_company_id', $insuranceCompany->id);
                })
                ->get();
            
            \Illuminate\Support\Facades\Log::info('👤 Clients found with matching phone', [
                'total_clients' => $clients->count(),
                'clients' => $clients->map(function($c) {
                    return [
                        'client_id' => $c->id,
                        'client_name' => $c->first_name . ' ' . $c->surname,
                        'cell_phone' => $c->cell_phone,
                        'whatsapp_line' => $c->whatsapp_line,
                    ];
                })->toArray(),
            ]);
            
            foreach ($clients as $client) {
                $policy = \App\Models\Policy::where('insurance_company_id', $insuranceCompany->id)
                    ->where(function($query) use ($client) {
                        $query->where('principal_member_id', $client->id)
                              ->orWhereHas('dependents', function($q) use ($client) {
                                  $q->where('clients.id', $client->id);
                              });
                    })
                    ->where('status', 'active')
                    ->with(['principalMember'])
                    ->first();

                if ($policy) {
                    $matchedPolicy = $policy;
                    $matchedClient = $client;
                    $verificationMethod = 'phone';
                    
                    \Illuminate\Support\Facades\Log::info('✅ Phone verification SUCCESS', [
                        'policy_number' => $policy->policy_number,
                        'client_id' => $client->id,
                    ]);
                    break;
                }
            }
            
            if (!$matchedPolicy) {
                if ($clients->count() > 0) {
                    \Illuminate\Support\Facades\Log::info('❌ Phone verification FAILED', [
                        'reason' => 'Client(s) found but no active policy with this insurance company',
                        'clients_found' => $clients->count(),
                    ]);
                    $attemptLog[] = 'Phone: Client found but no active policy';
                } else {
                    \Illuminate\Support\Facades\Log::info('❌ Phone verification FAILED', [
                        'reason' => 'No client found with matching phone number for this insurance company',
                    ]);
                    $attemptLog[] = 'Phone: No client found with matching phone';
                }
            }
        } elseif ($insuranceCompany->enable_phone_verification && empty($data['phone'])) {
            $attemptLog[] = 'Phone: Method enabled but no phone provided';
        }

        // Try email verification
        if (!$matchedPolicy && $insuranceCompany->enable_email_verification && !empty($data['email'])) {
            \Illuminate\Support\Facades\Log::info('🔍 Attempting Email verification', [
                'provided_email' => $data['email'],
                'insurance_company_id' => $insuranceCompany->id,
            ]);
            
            // Find clients with matching email that have policies with this insurance company
            $clients = \App\Models\Client::where('email', $data['email'])
                ->whereHas('policies', function($query) use ($insuranceCompany) {
                    $query->where('insurance_company_id', $insuranceCompany->id);
                })
                ->orWhereHas('principalMember.policies', function($query) use ($insuranceCompany) {
                    // Also check if this is a dependent whose principal has a policy
                    $query->where('insurance_company_id', $insuranceCompany->id);
                })
                ->get();
            
            \Illuminate\Support\Facades\Log::info('👤 Clients found with matching email', [
                'total_clients' => $clients->count(),
                'clients' => $clients->map(function($c) {
                    return [
                        'client_id' => $c->id,
                        'client_name' => $c->first_name . ' ' . $c->surname,
                        'client_dob' => $c->date_of_birth?->toDateString(),
                    ];
                })->toArray(),
            ]);
            
            foreach ($clients as $client) {
                // Check for any policy (active or inactive) first to see what exists
                $anyPolicy = \App\Models\Policy::where('insurance_company_id', $insuranceCompany->id)
                    ->where(function($query) use ($client) {
                        $query->where('principal_member_id', $client->id)
                              ->orWhereHas('dependents', function($q) use ($client) {
                                  $q->where('clients.id', $client->id);
                              });
                    })
                    ->with(['principalMember'])
                    ->get();
                
                \Illuminate\Support\Facades\Log::info('📋 Policies found for email client', [
                    'client_id' => $client->id,
                    'total_policies' => $anyPolicy->count(),
                    'policies' => $anyPolicy->map(function($p) {
                        return [
                            'policy_number' => $p->policy_number,
                            'status' => $p->status,
                            'insurance_company_id' => $p->insurance_company_id,
                        ];
                    })->toArray(),
                ]);
                
                $policy = \App\Models\Policy::where('insurance_company_id', $insuranceCompany->id)
                    ->where(function($query) use ($client) {
                        $query->where('principal_member_id', $client->id)
                              ->orWhereHas('dependents', function($q) use ($client) {
                                  $q->where('clients.id', $client->id);
                              });
                    })
                    ->where('status', 'active')
                    ->with(['principalMember'])
                    ->first();

                if ($policy) {
                    $matchedPolicy = $policy;
                    $matchedClient = $client;
                    $verificationMethod = 'email';
                    
                    \Illuminate\Support\Facades\Log::info('✅ Email verification SUCCESS', [
                        'policy_number' => $policy->policy_number,
                        'client_id' => $client->id,
                    ]);
                    break;
                } else {
                    $reason = 'Client found but no active policy with this insurance company';
                    if ($anyPolicy->count() > 0) {
                        $inactiveStatuses = $anyPolicy->pluck('status')->unique()->toArray();
                        $reason .= ' (found ' . $anyPolicy->count() . ' policy/policies with status: ' . implode(', ', $inactiveStatuses) . ')';
                    }
                    
                    \Illuminate\Support\Facades\Log::info('❌ Email verification FAILED for client', [
                        'reason' => $reason,
                        'client_id' => $client->id,
                    ]);
                }
            }
            
            if (!$matchedPolicy) {
                if ($clients->count() > 0) {
                    $attemptLog[] = 'Email: Client found but no active policy';
                } else {
                    \Illuminate\Support\Facades\Log::info('❌ Email verification FAILED', [
                        'reason' => 'No client found with matching email for this insurance company',
                    ]);
                    $attemptLog[] = 'Email: No client found with matching email';
                }
            }
        } elseif ($insuranceCompany->enable_email_verification && empty($data['email'])) {
            $attemptLog[] = 'Email: Method enabled but no email provided';
        }

        // If we found a match, check for mismatches and determine action
        if ($matchedPolicy) {
            $status = 'verified';
            $actionNeeded = null;

            // Only check for mismatches if verification was done via Name & DOB method
            // Mismatches are only relevant for Name & DOB verification where partial matches can occur
            if ($verificationMethod === 'name_dob' && !empty($mismatches)) {
                foreach ($mismatches as $mismatch) {
                    $actionField = $mismatch . '_mismatch_action';
                    $action = $insuranceCompany->$actionField ?? 'flag_for_review';
                    
                    if ($action === 'auto_reject') {
                        return [
                            'success' => false,
                            'message' => ucfirst($mismatch) . ' mismatch detected. Verification rejected.',
                            'status' => 'rejected',
                            'mismatches' => $mismatches,
                        ];
                    } else {
                        $status = 'flagged';
                        $actionNeeded = 'review';
                    }
                }
            } else {
                // For alternative verification methods (email, phone, id_passport, visit_id)
                // Check the action setting for that specific method
                $actionField = $verificationMethod . '_verification_action';
                $action = $insuranceCompany->$actionField ?? 'auto_accept';
                
                if ($action === 'auto_reject') {
                    return [
                        'success' => false,
                        'message' => 'Verification rejected based on insurance company settings.',
                        'status' => 'rejected',
                    ];
                } elseif ($action === 'flag_for_review') {
                    $status = 'flagged';
                    $actionNeeded = 'review';
                } else {
                    // auto_accept - status remains 'verified'
                    $status = 'verified';
                }
            }

            // Create visit verification record if visit_id provided
            if (!empty($data['visit_id'])) {
                $this->createVisitVerification($insuranceCompany, $data['visit_id'], $matchedPolicy, $matchedClient, $data, $status);
            }

            return [
                'success' => true,
                'message' => 'Client verified using ' . str_replace('_', ' ', $verificationMethod),
                'method' => $verificationMethod,
                'status' => $status,
                'warnings' => $warnings,
                'data' => [
                    'policy_number' => $matchedPolicy->policy_number,
                    'insurance_company_id' => $matchedPolicy->insurance_company_id,
                    'principal_member_id' => $matchedPolicy->principal_member_id,
                    'principal_member_name' => $matchedClient ? ($matchedClient->first_name . ' ' . $matchedClient->surname) : null,
                    'status' => $matchedPolicy->status,
                    'expiry_date' => $matchedPolicy->expiry_date?->toDateString(),
                ],
            ];
        }

        // Build detailed error message about what was attempted
        $attemptedMethods = [];
        $enabledMethods = [];
        
        if ($insuranceCompany->enable_visit_verification) {
            $enabledMethods[] = 'Visit ID';
            if (!empty($data['visit_id'])) {
                $attemptedMethods[] = 'Visit ID';
            }
        }
        
        if ($insuranceCompany->enable_name_dob_verification) {
            $enabledMethods[] = 'Name & Date of Birth';
            if (!empty($data['name']) && !empty($data['date_of_birth'])) {
                $attemptedMethods[] = 'Name & Date of Birth';
            }
        }
        
        if ($insuranceCompany->enable_id_passport_verification) {
            $enabledMethods[] = 'ID/Passport';
            if (!empty($data['id_passport_no'])) {
                $attemptedMethods[] = 'ID/Passport';
            }
        }
        
        if ($insuranceCompany->enable_phone_verification) {
            $enabledMethods[] = 'Phone';
            if (!empty($data['phone'])) {
                $attemptedMethods[] = 'Phone';
            }
        }
        
        if ($insuranceCompany->enable_email_verification) {
            $enabledMethods[] = 'Email';
            if (!empty($data['email'])) {
                $attemptedMethods[] = 'Email';
            }
        }
        
        \Illuminate\Support\Facades\Log::info('=== ALTERNATIVE VERIFICATION END ===', [
            'result' => $matchedPolicy ? 'SUCCESS' : 'FAILED',
            'verification_method' => $verificationMethod,
            'attempt_log' => $attemptLog,
            'enabled_methods' => $enabledMethods,
            'attempted_methods' => $attemptedMethods,
        ]);
        
        $message = 'No matching policy found.';
        
        if (empty($enabledMethods)) {
            $message .= ' No alternative verification methods are enabled for this insurance company.';
        } elseif (empty($attemptedMethods)) {
            $message .= ' Alternative verification methods are enabled (' . implode(', ', $enabledMethods) . '), but no matching data was provided.';
        } else {
            $message .= ' Attempted methods: ' . implode(', ', $attemptedMethods) . '. No matching policy was found.';
            if (!empty($attemptLog)) {
                $message .= ' Details: ' . implode('; ', $attemptLog);
            }
        }

        return [
            'success' => false,
            'message' => $message,
            'status' => 'not_found',
            'enabled_methods' => $enabledMethods,
            'attempted_methods' => $attemptedMethods,
            'attempt_log' => $attemptLog,
        ];
    }

    /**
     * Calculate name similarity percentage using Levenshtein distance
     */
    private function calculateNameSimilarity(string $name1, string $name2): int
    {
        $name1 = strtolower(trim($name1));
        $name2 = strtolower(trim($name2));
        
        if ($name1 === $name2) {
            return 100;
        }

        $maxLength = max(strlen($name1), strlen($name2));
        if ($maxLength === 0) {
            return 0;
        }

        $distance = levenshtein($name1, $name2);
        $similarity = (1 - ($distance / $maxLength)) * 100;
        
        return (int) round($similarity);
    }

    /**
     * Create visit verification record
     */
    private function createVisitVerification(InsuranceCompany $insuranceCompany, string $visitId, $policy, $client, array $providedData, string $status)
    {
        $expiresAt = now()->addDays($insuranceCompany->visit_verification_validity_days);

        \App\Models\VisitIdentityVerification::updateOrCreate(
            ['visit_id' => $visitId, 'insurance_company_id' => $insuranceCompany->id],
            [
                'policy_id' => $policy->id,
                'client_id' => $client->id ?? null,
                'provided_name' => $providedData['name'] ?? null,
                'provided_date_of_birth' => $providedData['date_of_birth'] ?? null,
                'provided_id_passport_no' => $providedData['id_passport_no'] ?? null,
                'provided_phone' => $providedData['phone'] ?? null,
                'provided_email' => $providedData['email'] ?? null,
                'matched_name' => $client ? ($client->first_name . ' ' . $client->surname) : null,
                'matched_date_of_birth' => $client->date_of_birth ?? null,
                'matched_id_passport_no' => $client->id_passport_no ?? null,
                'matched_phone' => $client->cell_phone ?? null,
                'matched_email' => $client->email ?? null,
                'verification_status' => $status,
                'verified_at' => now(),
                'expires_at' => $expiresAt,
            ]
        );
    }

    /**
     * Verify identity using visit ID
     */
    public function verifyVisitIdentity(Request $request, $insuranceCompanyId)
    {
        try {
            $validated = $request->validate([
                'visit_id' => 'required|string',
                'name' => 'nullable|string',
                'date_of_birth' => 'nullable|date',
                'id_passport_no' => 'nullable|string',
                'phone' => 'nullable|string',
                'email' => 'nullable|email',
            ]);

            $insuranceCompany = InsuranceCompany::findOrFail($insuranceCompanyId);

            if (!$insuranceCompany->enable_visit_verification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Visit-based verification is not enabled for this insurance company',
                ], 403);
            }

            // Check if visit verification already exists and is valid
            $visitVerification = \App\Models\VisitIdentityVerification::where('visit_id', $validated['visit_id'])
                ->where('insurance_company_id', $insuranceCompany->id)
                ->where('verification_status', 'verified')
                ->where('expires_at', '>', now())
                ->with(['policy', 'client'])
                ->first();

            if ($visitVerification && $visitVerification->policy) {
                return response()->json([
                    'success' => true,
                    'message' => 'Visit already verified',
                    'verification_method' => 'visit_id',
                    'verification_status' => 'verified',
                    'data' => [
                        'policy_number' => $visitVerification->policy->policy_number,
                        'insurance_company_id' => $visitVerification->policy->insurance_company_id,
                        'principal_member_id' => $visitVerification->policy->principal_member_id,
                        'visit_id' => $visitVerification->visit_id,
                        'verified_at' => $visitVerification->verified_at?->toIso8601String(),
                        'expires_at' => $visitVerification->expires_at?->toIso8601String(),
                    ],
                ], 200);
            }

            // Try to verify using alternative methods and create visit verification
            $verificationResult = $this->attemptAlternativeVerification($insuranceCompany, $validated);

            if ($verificationResult['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $verificationResult['message'],
                    'verification_method' => $verificationResult['method'],
                    'verification_status' => $verificationResult['status'],
                    'data' => $verificationResult['data'],
                    'warnings' => $verificationResult['warnings'] ?? [],
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => $verificationResult['message'],
                'verification_status' => $verificationResult['status'] ?? 'rejected',
                'mismatches' => $verificationResult['mismatches'] ?? [],
            ], 404);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to verify visit identity', [
                'insurance_company_id' => $insuranceCompanyId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to verify visit identity',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
