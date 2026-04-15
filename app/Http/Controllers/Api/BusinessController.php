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
                'country_name' => 'nullable|string|max:120',
                'currency_code' => 'nullable|string|max:10',
                'address' => 'nullable|string',
                'head_office_address' => 'nullable|string',
                'postal_address' => 'nullable|string',
                'website' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'open_enrollment_enabled' => 'nullable|boolean',
                
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
            $currencyCode = strtoupper(trim((string) ($validated['currency_code'] ?? 'UGX')));
            if ($currencyCode === '') {
                $currencyCode = 'UGX';
            }

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
                'country_name' => $validated['country_name'] ?? null,
                'currency_code' => $currencyCode,
                'head_office_address' => $headOfficeAddress,
                'postal_address' => $validated['postal_address'] ?? null,
                'website' => $validated['website'] ?? null,
                'description' => $validated['description'] ?? null,
                'open_enrollment_enabled' => (bool) ($validated['open_enrollment_enabled'] ?? false),
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
                'password' => Hash::make($validated['user_password']),
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
                        'country_name' => $insuranceCompany->country_name,
                        'currency_code' => $insuranceCompany->currency_code ?? 'UGX',
                        'head_office_address' => $insuranceCompany->head_office_address,
                        'postal_address' => $insuranceCompany->postal_address,
                        'website' => $insuranceCompany->website,
                        'description' => $insuranceCompany->description,
                        'open_enrollment_enabled' => (bool) $insuranceCompany->open_enrollment_enabled,
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
                    'country_name' => $insuranceCompany->country_name,
                    'currency_code' => $insuranceCompany->currency_code ?? 'UGX',
                    'head_office_address' => $insuranceCompany->head_office_address,
                    'postal_address' => $insuranceCompany->postal_address,
                    'website' => $insuranceCompany->website,
                    'description' => $insuranceCompany->description,
                    'is_active' => $insuranceCompany->is_active,
                    'verification_settings' => [
                        'require_physical_id' => $insuranceCompany->require_physical_id ?? true,
                        'enable_method_1' => $insuranceCompany->enable_method_1 ?? true,
                        'enable_method_2' => $insuranceCompany->enable_method_2 ?? false,
                        'enable_method_3' => $insuranceCompany->enable_method_3 ?? false,
                        'enable_method_4' => $insuranceCompany->enable_method_4 ?? false,
                    ],
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
     * Get insurance company settings by ID
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSettings($id)
    {
        try {
            $insuranceCompany = InsuranceCompany::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'message' => 'Insurance company settings retrieved successfully',
                'data' => [
                    'id' => $insuranceCompany->id,
                    'name' => $insuranceCompany->name,
                    'code' => $insuranceCompany->code,
                    'payment_responsibility_collection' => $insuranceCompany->payment_responsibility_collection ?? 'immediate',
                    'country_name' => $insuranceCompany->country_name,
                    'currency_code' => $insuranceCompany->currency_code ?? 'UGX',
                    'verification_settings' => [
                        'require_physical_id' => $insuranceCompany->require_physical_id ?? true,
                        'enable_method_1' => $insuranceCompany->enable_method_1 ?? true,
                        'enable_method_2' => $insuranceCompany->enable_method_2 ?? false,
                        'enable_method_3' => $insuranceCompany->enable_method_3 ?? false,
                        'enable_method_4' => $insuranceCompany->enable_method_4 ?? false,
                        'name_similarity_threshold' => $insuranceCompany->name_similarity_threshold ?? 80,
                        'dob_tolerance_days' => $insuranceCompany->dob_tolerance_days ?? 0,
                        'phone_otp_expiry_minutes' => $insuranceCompany->phone_otp_expiry_minutes ?? 5,
                        'email_otp_expiry_minutes' => $insuranceCompany->email_otp_expiry_minutes ?? 10,
                    ],
                    'open_enrollment' => [
                        'enabled'             => (bool) ($insuranceCompany->open_enrollment_enabled ?? false),
                        'min_age'             => $insuranceCompany->open_enrollment_min_age,
                        'max_age'             => $insuranceCompany->open_enrollment_max_age,
                        'genders'             => $insuranceCompany->open_enrollment_genders ?? [],
                        'service_categories'  => $insuranceCompany->open_enrollment_service_categories ?? [],
                        'start_date'          => $insuranceCompany->open_enrollment_start_date?->toDateString(),
                        'end_date'            => $insuranceCompany->open_enrollment_end_date?->toDateString(),
                        'max_invoice_amount'  => $insuranceCompany->open_enrollment_max_invoice_amount ? (float) $insuranceCompany->open_enrollment_max_invoice_amount : null,
                        'nationalities'       => $insuranceCompany->open_enrollment_nationalities ?? [],
                        'marital_statuses'    => $insuranceCompany->open_enrollment_marital_statuses ?? [],
                        'client_types'        => $insuranceCompany->open_enrollment_client_types ?? [],
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Insurance company not found.',
                'data' => null,
            ], 404);
        }
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
                'country_name' => $insuranceCompany->country_name,
                'currency_code' => $insuranceCompany->currency_code ?? 'UGX',
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
                        'country_name' => $insuranceCompany->country_name,
                        'currency_code' => $insuranceCompany->currency_code ?? 'UGX',
                        'head_office_address' => $insuranceCompany->head_office_address,
                        'postal_address' => $insuranceCompany->postal_address,
                        'website' => $insuranceCompany->website,
                    ],
                    'verification_settings' => [
                        'require_physical_id' => $insuranceCompany->require_physical_id ?? true,
                        'enable_method_1' => $insuranceCompany->enable_method_1 ?? true,
                        'enable_method_2' => $insuranceCompany->enable_method_2 ?? false,
                        'enable_method_3' => $insuranceCompany->enable_method_3 ?? false,
                        'enable_method_4' => $insuranceCompany->enable_method_4 ?? false,
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
            \Illuminate\Support\Facades\Log::info('=== API: verifyPolicyNumber START ===', [
                'insurance_company_id' => $insuranceCompanyId,
                'route_policy_number' => $policyNumber,
                'request_method' => $request->method(),
                'request_url' => $request->fullUrl(),
                'request_all' => $request->all(),
                'query_params' => $request->query(),
            ]);
            
            $insuranceCompany = InsuranceCompany::findOrFail($insuranceCompanyId);
            
            \Illuminate\Support\Facades\Log::info('API: Insurance company found', [
                'insurance_company_id' => $insuranceCompany->id,
                'insurance_company_name' => $insuranceCompany->name,
                'insurance_company_code' => $insuranceCompany->code,
            ]);
            
            // Get policy number from route parameter or request
            $policyNumber = $policyNumber ?? $request->input('policy_number');
            
            \Illuminate\Support\Facades\Log::info('API: Policy number extracted', [
                'policy_number' => $policyNumber,
                'has_policy_number' => !empty($policyNumber),
            ]);
            
            // If no policy number provided, try alternative verification (name + DOB)
            if (!$policyNumber) {
                $name = $request->input('name');
                $dateOfBirth = $request->input('date_of_birth');
                
                \Illuminate\Support\Facades\Log::info('API: No policy number, attempting alternative verification', [
                    'has_name' => !empty($name),
                    'has_dob' => !empty($dateOfBirth),
                    'name' => $name,
                    'date_of_birth' => $dateOfBirth,
                ]);
                
                if (!$name || !$dateOfBirth) {
                    $response = response()->json([
                        'success' => false,
                        'message' => 'Policy number or name and date of birth are required.',
                        'exists' => false,
                    ], 400);
                    
                    \Illuminate\Support\Facades\Log::info('API: Alternative verification failed - missing data', [
                        'response' => $response->getContent(),
                    ]);
                    
                    return $response;
                }
                
                // Try alternative verification using name and DOB only
                $result = $this->verifyByNameAndDob($insuranceCompany, $name, $dateOfBirth);
                
                \Illuminate\Support\Facades\Log::info('API: Alternative verification result', [
                    'response_status' => $result->getStatusCode(),
                    'response_content' => $result->getContent(),
                ]);
                
                return $result;
            }
            
            // Step 1: Check if policy number exists (for this insurance company only)
            // Normalize policy number (trim, uppercase, remove extra spaces)
            $normalizedPolicyNumber = strtoupper(trim($policyNumber));
            
            \Illuminate\Support\Facades\Log::info('Verifying policy number', [
                'insurance_company_id' => $insuranceCompanyId,
                'original_policy_number' => $policyNumber,
                'normalized_policy_number' => $normalizedPolicyNumber,
            ]);
            
            // Try exact match first (any status) – status is returned for display/decisions on Kashtre side
            $policy = \App\Models\Policy::where('insurance_company_id', $insuranceCompanyId)
                ->where('policy_number', $normalizedPolicyNumber)
                ->with(['principalMember', 'insuranceCompany'])
                ->first();

            \Illuminate\Support\Facades\Log::info('Policy lookup attempt 1 (exact match)', [
                'insurance_company_id' => $insuranceCompanyId,
                'normalized_policy_number' => $normalizedPolicyNumber,
                'found' => $policy ? true : false,
                'policy_id' => $policy ? $policy->id : null,
            ]);

            // If not found, try case-insensitive search (still scoped to this insurance company, any status)
            if (!$policy) {
                $policy = \App\Models\Policy::where('insurance_company_id', $insuranceCompanyId)
                    ->whereRaw('UPPER(TRIM(policy_number)) = ?', [strtoupper(trim($policyNumber))])
                    ->with(['principalMember', 'insuranceCompany'])
                    ->first();
                
                \Illuminate\Support\Facades\Log::info('Policy lookup attempt 2 (case-insensitive)', [
                    'insurance_company_id' => $insuranceCompanyId,
                    'policy_number' => $policyNumber,
                    'found' => $policy ? true : false,
                    'policy_id' => $policy ? $policy->id : null,
                ]);
            }
            
            // If still not found for this insurance company, try open enrollment fallback
            if (!$policy) {
                $insuranceCompanyDetails = \App\Models\InsuranceCompany::find($insuranceCompanyId);

                // Open enrollment fallback — check criteria using provided name/DOB/gender
                if (
                    $insuranceCompanyDetails &&
                    $insuranceCompanyDetails->open_enrollment_enabled &&
                    $insuranceCompanyDetails->generic_policy_id
                ) {
                    $openResult = $this->verifyOpenEnrollment(
                        $insuranceCompanyDetails,
                        $request->input('date_of_birth'),
                        $request->input('gender'),
                        $request->input('services_category'),
                        $request->input('invoice_amount') !== null ? (float) $request->input('invoice_amount') : null,
                        $request->input('nationality'),
                        $request->input('marital_status'),
                        $request->input('client_type')
                    );

                    if ($openResult !== null) {
                        // Check if the function returned a failure reason
                        if ($openResult['_failed'] ?? false) {
                            \Illuminate\Support\Facades\Log::info('API: Open enrollment criteria not met', [
                                'insurance_company_id' => $insuranceCompanyId,
                                'reason' => $openResult['reason'],
                            ]);
                            return response()->json([
                                'success' => false,
                                'message' => $openResult['reason'],
                                'exists'  => false,
                            ], 422);
                        }

                        \Illuminate\Support\Facades\Log::info('API: Open enrollment match — returning generic policy', [
                            'insurance_company_id' => $insuranceCompanyId,
                            'policy_number_queried' => $policyNumber,
                        ]);
                        return response()->json($openResult, 200);
                    }

                    \Illuminate\Support\Facades\Log::info('API: Open enrollment enabled but client does not meet criteria', [
                        'insurance_company_id'  => $insuranceCompanyId,
                        'dob'                   => $request->input('date_of_birth'),
                        'gender'                => $request->input('gender'),
                        'services_category'     => $request->input('services_category'),
                        'invoice_amount'        => $request->input('invoice_amount'),
                        'nationality'           => $request->input('nationality'),
                        'marital_status'        => $request->input('marital_status'),
                        'client_type'           => $request->input('client_type'),
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Client does not meet the open enrollment criteria.',
                        'exists' => false,
                    ], 422);
                }

                // Debug: Check what policies exist for this insurance company (last 10)
                $recentPolicies = \App\Models\Policy::where('insurance_company_id', $insuranceCompanyId)
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->pluck('policy_number', 'status')
                    ->toArray();

                // Also check if policy exists with ANY insurance company (for debugging)
                $policyAnywhere = \App\Models\Policy::where('policy_number', $normalizedPolicyNumber)
                    ->orWhereRaw('UPPER(TRIM(policy_number)) = ?', [strtoupper(trim($policyNumber))])
                    ->with(['insuranceCompany'])
                    ->first();

                // Get all insurance companies for this business (if business_id is available)
                $allInsuranceCompanies = \App\Models\InsuranceCompany::where('id', $insuranceCompanyId)
                    ->orWhere('code', $insuranceCompanyDetails->code ?? '')
                    ->get(['id', 'name', 'code'])
                    ->toArray();

                $errorResponse = [
                    'success' => false,
                    'message' => 'Client not found.',
                    'exists' => false,
                ];

                \Illuminate\Support\Facades\Log::warning('API: Policy number NOT FOUND', [
                    'insurance_company_id' => $insuranceCompanyId,
                    'insurance_company_name' => $insuranceCompanyDetails->name ?? 'N/A',
                    'insurance_company_code' => $insuranceCompanyDetails->code ?? 'N/A',
                    'policy_number' => $policyNumber,
                    'normalized' => $normalizedPolicyNumber,
                    'recent_policies_for_company' => $recentPolicies,
                    'policy_exists_anywhere' => $policyAnywhere ? [
                        'found' => true,
                        'policy_number' => $policyAnywhere->policy_number,
                        'insurance_company_id' => $policyAnywhere->insurance_company_id,
                        'insurance_company_name' => $policyAnywhere->insuranceCompany->name ?? 'N/A',
                        'insurance_company_code' => $policyAnywhere->insuranceCompany->code ?? 'N/A',
                        'status' => $policyAnywhere->status,
                    ] : ['found' => false],
                    'all_insurance_companies' => $allInsuranceCompanies,
                    'response_data' => $errorResponse,
                ]);

                return response()->json($errorResponse, 404);
            }
            
            \Illuminate\Support\Facades\Log::info('Policy found', [
                'policy_number' => $policy->policy_number,
                'status' => $policy->status,
                'principal_member' => $policy->principalMember ? ($policy->principalMember->first_name . ' ' . $policy->principalMember->surname) : null,
            ]);

            // Step 2: If policy exists, verify name and DOB (if provided)
            $providedName = $request->input('name');
            $providedDob = $request->input('date_of_birth');
            
            $warnings = [];
            $verificationStatus = 'verified';
            $errors = [];
            
            // Verify name if provided
            if ($providedName && $policy->principalMember) {
                // Build client name from database (first_name + surname + other_names)
                $clientName = trim(($policy->principalMember->first_name ?? '') . ' ' . ($policy->principalMember->surname ?? ''));
                if (!empty($policy->principalMember->other_names)) {
                    $clientName = trim($clientName . ' ' . $policy->principalMember->other_names);
                }
                $providedName = trim($providedName);
                $similarity = $this->calculateNameSimilarity($clientName, $providedName);
                $nameThreshold = $insuranceCompany->name_similarity_threshold ?? 80;
                
                \Illuminate\Support\Facades\Log::info('Name comparison in policy verification', [
                    'policy_number' => $policy->policy_number,
                    'client_first_name' => $policy->principalMember->first_name ?? 'N/A',
                    'client_surname' => $policy->principalMember->surname ?? 'N/A',
                    'client_other_names' => $policy->principalMember->other_names ?? 'N/A',
                    'client_name_full' => $clientName,
                    'provided_name' => $providedName,
                    'similarity' => $similarity,
                    'threshold' => $nameThreshold,
                ]);
                
                if ($similarity < $nameThreshold) {
                    $errors[] = "Name does not match. Similarity: {$similarity}% (required: {$nameThreshold}%)";
                    $verificationStatus = 'rejected';
                } elseif ($similarity < 100) {
                    $warnings[] = "Name similarity: {$similarity}%";
                }
            }
            
            // Verify DOB if provided
            if ($providedDob && $policy->principalMember && $policy->principalMember->date_of_birth) {
                $clientDob = \Carbon\Carbon::parse($policy->principalMember->date_of_birth);
                $providedDobParsed = \Carbon\Carbon::parse($providedDob);
                $daysDiff = abs($clientDob->diffInDays($providedDobParsed));
                $dobTolerance = $insuranceCompany->dob_tolerance_days ?? 0;
                
                if ($daysDiff > $dobTolerance) {
                    $errors[] = "Date of birth does not match. Difference: {$daysDiff} days (allowed: {$dobTolerance} days)";
                    $verificationStatus = 'rejected';
                } elseif ($daysDiff > 0) {
                    $warnings[] = "Date of birth difference: {$daysDiff} days";
                }
            }
            
            // If verification failed (name and/or DOB mismatch), treat as "client not found"
            if ($verificationStatus === 'rejected' && !empty($errors)) {
                $errorResponse = [
                    'success' => false,
                    'message' => 'Client not found.',
                    'exists' => false,
                    'verification_status' => 'rejected',
                    'errors' => $errors,
                ];
                
                \Illuminate\Support\Facades\Log::warning('API: Policy verification REJECTED', [
                    'policy_number' => $policyNumber,
                    'response_data' => $errorResponse,
                ]);
                
                return response()->json($errorResponse, 422);
            }
            
            // Check service category exclusions for this connected company
            $requestedCategory = $request->input('services_category');
            $connectedBusinessId = $request->input('connected_business_id');

            if ($requestedCategory && $connectedBusinessId) {
                $connection = \App\Models\BusinessConnection::where('insurance_company_id', $insuranceCompanyId)
                    ->where('connected_business_id', $connectedBusinessId)
                    ->first();

                if ($connection) {
                    $excluded = \App\Models\ConnectedCompanyServiceExclusion::where('insurance_company_id', $insuranceCompanyId)
                        ->where('business_connection_id', $connection->id)
                        ->where('is_active', true)
                        ->whereNotNull('service_category')
                        ->whereNull('service_code')
                        ->get()
                        ->first(fn($ex) => strtolower(trim($ex->service_category)) === strtolower(trim($requestedCategory)));

                    if ($excluded) {
                        \Illuminate\Support\Facades\Log::warning('API: Policy verification REJECTED — service category excluded for this provider', [
                            'policy_number'       => $policy->policy_number,
                            'services_category'   => $requestedCategory,
                            'connected_business_id' => $connectedBusinessId,
                            'exclusion_reason'    => $excluded->reason,
                        ]);

                        return response()->json([
                            'success' => false,
                            'message' => ucfirst($requestedCategory) . ' services are not covered for your facility' . ($excluded->reason ? ': ' . $excluded->reason : '.'),
                            'exists'  => false,
                        ], 422);
                    }
                }
            }

            // Check that the policy's benefits cover the requested service category.
            // Only enforced when the policy has at least one benefit defined.
            if ($requestedCategory) {
                $policyBenefits = $policy->benefits()->where('is_enabled', true)->with('serviceCategory')->get();

                if ($policyBenefits->isNotEmpty()) {
                    $normalised = strtolower(trim($requestedCategory));

                    $covered = $policyBenefits->first(function ($benefit) use ($normalised) {
                        $cat = $benefit->serviceCategory;
                        if (!$cat) {
                            return false;
                        }
                        // Match against slug (e.g. "outpatient") or slug prefix (e.g. "funeral" → "funeral-expenses")
                        $slug = strtolower(trim($cat->slug ?? ''));
                        $name = strtolower(trim($cat->name ?? ''));

                        return $slug === $normalised
                            || str_starts_with($slug, $normalised)
                            || $name === $normalised
                            || str_starts_with($name, $normalised);
                    });

                    if (!$covered) {
                        \Illuminate\Support\Facades\Log::warning('API: Policy verification REJECTED — service category not covered by policy benefits', [
                            'policy_number'     => $policy->policy_number,
                            'services_category' => $requestedCategory,
                            'covered_categories' => $policyBenefits->map(fn($b) => $b->serviceCategory?->slug)->filter()->values(),
                        ]);

                        return response()->json([
                            'success' => false,
                            'message' => ucfirst($requestedCategory) . ' is not covered under this policy.',
                            'exists'  => false,
                        ], 422);
                    }
                }
            }

            // Build payment responsibility information
            $benefitBalance = null;
            if ($policy->principalMember) {
                $account = $policy->principalMember->account;
                if ($account) {
                    $benefitBalance = (float) $account->available_balance;
                }
            }

            $paymentInfo = [
                'benefit_balance' => $benefitBalance,
                'has_deductible' => $policy->has_deductible ?? false,
                'deductible_amount' => $policy->deductible_amount ? (float)$policy->deductible_amount : null,
                'copay_amount' => $policy->copay_amount ? (float)$policy->copay_amount : null,
                'coinsurance_percentage' => $policy->coinsurance_percentage ? (float)$policy->coinsurance_percentage : null,
                'copay_max_limit' => $policy->copay_max_limit ? (float)$policy->copay_max_limit : null,
                'copay_contributes_to_deductible' => $policy->copayContributesToDeductible(),
                'coinsurance_contributes_to_deductible' => $policy->coinsuranceContributesToDeductible(),
            ];
            
            $responseData = [
                        'success' => true,
                'message' => 'Policy number verified' . (!empty($warnings) ? ' with warnings' : ''),
                        'exists' => true,
                        'verification_method' => 'policy_number',
                'verification_status' => $verificationStatus,
                        'data' => [
                            'policy_number' => $policy->policy_number,
                            'insurance_company_id' => $policy->insurance_company_id,
                            'insurance_company_name' => $policy->insuranceCompany->name ?? null,
                            'principal_member_id' => $policy->principal_member_id,
                            'principal_member_name' => $policy->principalMember ? ($policy->principalMember->first_name . ' ' . $policy->principalMember->surname) : null,
                            'status' => $policy->status,
                            'expiry_date' => $policy->expiry_date?->toDateString(),
                    'payment_responsibility' => $paymentInfo,
                ],
                'warnings' => $warnings,
            ];
            
            \Illuminate\Support\Facades\Log::info('API: Policy verification SUCCESS - returning response', [
                'response_data' => $responseData,
            ]);
            
            return response()->json($responseData, 200);

        } catch (\Exception $e) {
            $errorResponse = [
                'success' => false,
                'message' => 'Failed to verify policy number',
                'error' => $e->getMessage(),
            ];
            
            \Illuminate\Support\Facades\Log::error('API: Exception in verifyPolicyNumber', [
                'insurance_company_id' => $insuranceCompanyId,
                'policy_number' => $policyNumber,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'response_data' => $errorResponse,
            ]);

            return response()->json($errorResponse, 500);
        } finally {
            \Illuminate\Support\Facades\Log::info('=== API: verifyPolicyNumber END ===');
        }
    }

    /**
     * Check all open enrollment criteria and return a verification response using the
     * insurer's generic policy, or null if any criterion is not met.
     *
     * Criteria evaluated (all optional — omitted = no restriction):
     *   age range, gender, service category, enrollment window,
     *   max invoice amount, nationality, marital status, client type.
     *
     * The caller passes request params that Kashtre already sends during verification.
     */
    private function verifyOpenEnrollment(
        InsuranceCompany $insuranceCompany,
        ?string $dateOfBirth,
        ?string $gender,
        ?string $servicesCategory = null,
        ?float  $invoiceAmount    = null,
        ?string $nationality      = null,
        ?string $maritalStatus    = null,
        ?string $clientType       = null
    ): ?array {
        $genericPolicy = $insuranceCompany->genericPolicy()->with('insuranceCompany')->first();
        if (!$genericPolicy) {
            return null;
        }

        $matchedCriteria = [];
        $failReason = null;

        // ── 1. Age ────────────────────────────────────────────────────────────
        $minAge = $insuranceCompany->open_enrollment_min_age;
        $maxAge = $insuranceCompany->open_enrollment_max_age;
        if ($minAge !== null || $maxAge !== null) {
            if (!$dateOfBirth) {
                $failReason = 'Date of birth is required to verify age eligibility.';
            } else {
                $age = (int) \Carbon\Carbon::parse($dateOfBirth)->diffInYears(now());
                if ($minAge !== null && $age < $minAge) {
                    $failReason = "Client age ({$age}) is below the minimum required age of {$minAge}.";
                } elseif ($maxAge !== null && $age > $maxAge) {
                    $failReason = "Client age ({$age}) exceeds the maximum allowed age of {$maxAge}.";
                } else {
                    $matchedCriteria[] = "age {$age}";
                }
            }
        }
        if ($failReason) return ['_failed' => true, 'reason' => $failReason];

        // ── 2. Gender ─────────────────────────────────────────────────────────
        $allowedGenders = $insuranceCompany->open_enrollment_genders ?? [];
        if (!empty($allowedGenders)) {
            if (!$gender || !in_array(ucfirst(strtolower($gender)), $allowedGenders)) {
                $failReason = 'Client gender (' . ($gender ?: 'not provided') . ') is not covered. Allowed: ' . implode(', ', $allowedGenders) . '.';
            } else {
                $matchedCriteria[] = "gender {$gender}";
            }
        }
        if ($failReason) return ['_failed' => true, 'reason' => $failReason];

        // ── 3. Service category ───────────────────────────────────────────────
        $allowedCategories = $insuranceCompany->open_enrollment_service_categories ?? [];
        if (!empty($allowedCategories)) {
            if (!$servicesCategory) {
                $failReason = 'Service category is required. Allowed: ' . implode(', ', $allowedCategories) . '.';
            } elseif (!in_array(strtolower($servicesCategory), array_map('strtolower', $allowedCategories))) {
                $failReason = ucfirst($servicesCategory) . ' is not covered under this plan. Covered categories: ' . implode(', ', $allowedCategories) . '.';
            } else {
                $matchedCriteria[] = "category {$servicesCategory}";
            }
        }
        if ($failReason) return ['_failed' => true, 'reason' => $failReason];

        // ── 4. Enrollment window ──────────────────────────────────────────────
        $startDate = $insuranceCompany->open_enrollment_start_date;
        $endDate   = $insuranceCompany->open_enrollment_end_date;
        if ($startDate && now()->lt($startDate)) {
            return ['_failed' => true, 'reason' => 'Enrollment has not started yet. It opens on ' . $startDate->toDateString() . '.'];
        }
        if ($endDate && now()->gt($endDate)) {
            return ['_failed' => true, 'reason' => 'Enrollment period has closed (ended ' . $endDate->toDateString() . ').'];
        }
        if ($startDate || $endDate) {
            $matchedCriteria[] = 'within enrollment window';
        }

        // ── 5. Max invoice amount ─────────────────────────────────────────────
        $maxAmount = $insuranceCompany->open_enrollment_max_invoice_amount;
        if ($maxAmount !== null && $invoiceAmount !== null && $invoiceAmount > (float) $maxAmount) {
            return ['_failed' => true, 'reason' => 'Invoice amount exceeds the maximum allowed (' . number_format($maxAmount) . ').'];
        }
        if ($maxAmount !== null && $invoiceAmount !== null) {
            $matchedCriteria[] = "amount within cap";
        }

        // ── 6. Nationality ────────────────────────────────────────────────────
        $allowedNationalities = $insuranceCompany->open_enrollment_nationalities ?? [];
        if (!empty($allowedNationalities)) {
            if (!$nationality || !in_array(
                strtolower(trim($nationality)),
                array_map('strtolower', array_map('trim', $allowedNationalities))
            )) {
                return ['_failed' => true, 'reason' => 'Client nationality (' . ($nationality ?: 'not provided') . ') is not eligible. Allowed: ' . implode(', ', $allowedNationalities) . '.'];
            }
            $matchedCriteria[] = "nationality {$nationality}";
        }

        // ── 7. Marital status ─────────────────────────────────────────────────
        $allowedMarital = $insuranceCompany->open_enrollment_marital_statuses ?? [];
        if (!empty($allowedMarital)) {
            if (!$maritalStatus || !in_array(ucfirst(strtolower($maritalStatus)), $allowedMarital)) {
                return ['_failed' => true, 'reason' => 'Client marital status (' . ($maritalStatus ?: 'not provided') . ') is not eligible. Allowed: ' . implode(', ', $allowedMarital) . '.'];
            }
            $matchedCriteria[] = "marital status {$maritalStatus}";
        }

        // ── 8. Client type ────────────────────────────────────────────────────
        $allowedTypes = $insuranceCompany->open_enrollment_client_types ?? [];
        if (!empty($allowedTypes)) {
            if (!$clientType || !in_array(strtolower($clientType), array_map('strtolower', $allowedTypes))) {
                return ['_failed' => true, 'reason' => 'Client type (' . ($clientType ?: 'not provided') . ') is not eligible. Allowed: ' . implode(', ', $allowedTypes) . '.'];
            }
            $matchedCriteria[] = "type {$clientType}";
        }

        // ── All criteria passed ───────────────────────────────────────────────
        $paymentInfo = [
            'has_deductible'                        => $genericPolicy->has_deductible ?? false,
            'deductible_amount'                     => $genericPolicy->deductible_amount ? (float) $genericPolicy->deductible_amount : null,
            'copay_amount'                          => $genericPolicy->copay_amount ? (float) $genericPolicy->copay_amount : null,
            'coinsurance_percentage'                => $genericPolicy->coinsurance_percentage ? (float) $genericPolicy->coinsurance_percentage : null,
            'copay_max_limit'                       => $genericPolicy->copay_max_limit ? (float) $genericPolicy->copay_max_limit : null,
            'copay_contributes_to_deductible'       => $genericPolicy->copayContributesToDeductible(),
            'coinsurance_contributes_to_deductible' => $genericPolicy->coinsuranceContributesToDeductible(),
        ];

        return [
            'success'             => true,
            'message'             => 'Client verified via open enrollment'
                                     . (count($matchedCriteria) ? ' (' . implode(', ', $matchedCriteria) . ')' : ''),
            'exists'              => true,
            'verification_method' => 'open_enrollment',
            'verification_status' => 'verified',
            'data'                => [
                'policy_number'          => $genericPolicy->policy_number,
                'insurance_company_id'   => $genericPolicy->insurance_company_id,
                'insurance_company_name' => $genericPolicy->insuranceCompany->name ?? null,
                'principal_member_id'    => null,
                'principal_member_name'  => null,
                'status'                 => $genericPolicy->status,
                'expiry_date'            => $genericPolicy->expiry_date?->toDateString(),
                'payment_responsibility' => $paymentInfo,
                'open_enrollment'        => true,
                'max_invoice_amount'     => $maxAmount ? (float) $maxAmount : null,
            ],
            'warnings' => [],
        ];
    }

    /**
     * Verify client using name and date of birth only (alternative verification)
     */
    private function verifyByNameAndDob(InsuranceCompany $insuranceCompany, string $name, string $dateOfBirth)
    {
        \Illuminate\Support\Facades\Log::info('=== API: verifyByNameAndDob START ===', [
            'insurance_company_id' => $insuranceCompany->id,
            'provided_name' => $name,
            'provided_dob' => $dateOfBirth,
        ]);
        
        $policies = \App\Models\Policy::where('insurance_company_id', $insuranceCompany->id)
            ->where('status', 'active')
            ->with(['principalMember', 'insuranceCompany'])
            ->get();

        \Illuminate\Support\Facades\Log::info('API: Found policies for name/DOB verification', [
            'total_policies' => $policies->count(),
        ]);

        $nameThreshold = $insuranceCompany->name_similarity_threshold ?? 80;
        $dobTolerance = $insuranceCompany->dob_tolerance_days ?? 0;
        
        $bestMatch = null;
        $bestSimilarity = 0;
        $closestDobDiff = null;

        foreach ($policies as $policy) {
            if (!$policy->principalMember) {
                continue;
            }
            
            // Build client name from database (first_name + surname)
            $clientName = trim(($policy->principalMember->first_name ?? '') . ' ' . ($policy->principalMember->surname ?? ''));
            // Also check if other_names exists and add it
            if (!empty($policy->principalMember->other_names)) {
                $clientName = trim($clientName . ' ' . $policy->principalMember->other_names);
            }
            $providedName = trim($name);
            $similarity = $this->calculateNameSimilarity($clientName, $providedName);
            
            \Illuminate\Support\Facades\Log::info('Name comparison in verifyByNameAndDob', [
                'policy_number' => $policy->policy_number,
                'client_first_name' => $policy->principalMember->first_name ?? 'N/A',
                'client_surname' => $policy->principalMember->surname ?? 'N/A',
                'client_other_names' => $policy->principalMember->other_names ?? 'N/A',
                'client_name_full' => $clientName,
                'provided_name' => $providedName,
                'similarity' => $similarity,
                'threshold' => $nameThreshold,
            ]);
            
            $dobMatch = false;
            $daysDiff = null;
            if ($policy->principalMember->date_of_birth) {
                $clientDob = \Carbon\Carbon::parse($policy->principalMember->date_of_birth);
                $providedDob = \Carbon\Carbon::parse($dateOfBirth);
                $daysDiff = abs($clientDob->diffInDays($providedDob));
                $dobMatch = $daysDiff <= $dobTolerance;
                
                \Illuminate\Support\Facades\Log::info('DOB comparison in verifyByNameAndDob', [
                    'policy_number' => $policy->policy_number,
                    'client_dob' => $clientDob->toDateString(),
                    'provided_dob' => $providedDob->toDateString(),
                    'days_diff' => $daysDiff,
                    'tolerance' => $dobTolerance,
                    'match' => $dobMatch,
                ]);
            }

            // Track best match
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

            // Check if both name and DOB match
            if ($similarity >= $nameThreshold && $dobMatch) {
                $warnings = [];
                if ($similarity < 100) {
                    $warnings[] = "Name similarity: {$similarity}%";
                }
                if ($daysDiff > 0) {
                    $warnings[] = "Date of birth difference: {$daysDiff} days";
                }
                
                // Build payment responsibility information
                $paymentInfo = [
                    'has_deductible' => $policy->has_deductible ?? false,
                    'deductible_amount' => $policy->deductible_amount ? (float)$policy->deductible_amount : null,
                    'copay_amount' => $policy->copay_amount ? (float)$policy->copay_amount : null,
                    'coinsurance_percentage' => $policy->coinsurance_percentage ? (float)$policy->coinsurance_percentage : null,
                    'copay_max_limit' => $policy->copay_max_limit ? (float)$policy->copay_max_limit : null,
                    'copay_contributes_to_deductible' => $policy->copayContributesToDeductible(),
                    'coinsurance_contributes_to_deductible' => $policy->coinsuranceContributesToDeductible(),
                ];
                
                $successResponse = [
                    'success' => true,
                    'message' => 'Client verified using name and date of birth',
                    'exists' => true,
                    'verification_method' => 'name_dob',
                    'verification_status' => 'verified',
                    'data' => [
                        'policy_number' => $policy->policy_number,
                        'insurance_company_id' => $policy->insurance_company_id,
                        'insurance_company_name' => $policy->insuranceCompany->name ?? null,
                        'principal_member_id' => $policy->principal_member_id,
                        'principal_member_name' => $policy->principalMember ? ($policy->principalMember->first_name . ' ' . $policy->principalMember->surname) : null,
                        'status' => $policy->status,
                        'expiry_date' => $policy->expiry_date?->toDateString(),
                        'payment_responsibility' => $paymentInfo,
                    ],
                    'warnings' => $warnings,
                ];
                
                \Illuminate\Support\Facades\Log::info('API: verifyByNameAndDob - MATCH FOUND', [
                    'policy_number' => $policy->policy_number,
                    'name_similarity' => $similarity,
                    'dob_difference' => $daysDiff,
                    'response_data' => $successResponse,
                ]);
                
                return response()->json($successResponse, 200);
            }
        }
        
        // No match found - return detailed error
        $errorMessage = 'No matching policy found.';
        $details = [];
        
        if ($bestMatch) {
            $details[] = "Best name match: {$bestMatch['client_name']} ({$bestMatch['similarity']}% similarity, required: {$nameThreshold}%)";
        }
        if ($closestDobDiff !== null) {
            $details[] = "Closest DOB difference: {$closestDobDiff} days (allowed: {$dobTolerance} days)";
        }
        
        if (!empty($details)) {
            $errorMessage .= ' ' . implode('; ', $details);
        }
        
        $errorResponse = [
                'success' => false,
            'message' => $errorMessage,
                'exists' => false,
            'verification_status' => 'not_found',
            'details' => $details,
        ];
        
        \Illuminate\Support\Facades\Log::warning('API: verifyByNameAndDob - NO MATCH FOUND', [
            'provided_name' => $name,
            'provided_dob' => $dateOfBirth,
            'best_match' => $bestMatch,
            'closest_dob_diff' => $closestDobDiff,
            'response_data' => $errorResponse,
        ]);
        
        return response()->json($errorResponse, 404);
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
                
                // Build payment responsibility information
                $paymentInfo = [
                    'has_deductible' => $visitVerification->policy->has_deductible ?? false,
                    'deductible_amount' => $visitVerification->policy->deductible_amount ? (float)$visitVerification->policy->deductible_amount : null,
                    'copay_amount' => $visitVerification->policy->copay_amount ? (float)$visitVerification->policy->copay_amount : null,
                    'coinsurance_percentage' => $visitVerification->policy->coinsurance_percentage ? (float)$visitVerification->policy->coinsurance_percentage : null,
                    'copay_max_limit' => $visitVerification->policy->copay_max_limit ? (float)$visitVerification->policy->copay_max_limit : null,
                    'copay_contributes_to_deductible' => $visitVerification->policy->copayContributesToDeductible(),
                    'coinsurance_contributes_to_deductible' => $visitVerification->policy->coinsuranceContributesToDeductible(),
                ];
                
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
                        'payment_responsibility' => $paymentInfo,
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

            // Build payment responsibility information
            $paymentInfo = [
                'has_deductible' => $matchedPolicy->has_deductible ?? false,
                'deductible_amount' => $matchedPolicy->deductible_amount ? (float)$matchedPolicy->deductible_amount : null,
                'copay_amount' => $matchedPolicy->copay_amount ? (float)$matchedPolicy->copay_amount : null,
                'coinsurance_percentage' => $matchedPolicy->coinsurance_percentage ? (float)$matchedPolicy->coinsurance_percentage : null,
                'copay_max_limit' => $matchedPolicy->copay_max_limit ? (float)$matchedPolicy->copay_max_limit : null,
                'copay_contributes_to_deductible' => $matchedPolicy->copayContributesToDeductible(),
                'coinsurance_contributes_to_deductible' => $matchedPolicy->coinsuranceContributesToDeductible(),
            ];

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
                    'payment_responsibility' => $paymentInfo,
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

        // Split names into words
        $name1Parts = array_filter(array_map('trim', explode(' ', $name1)));
        $name2Parts = array_filter(array_map('trim', explode(' ', $name2)));
        
        if (count($name1Parts) === 0 || count($name2Parts) === 0) {
            return $this->calculateLevenshteinSimilarity($name1, $name2);
        }
        
        // Sort parts for comparison
        $name1PartsSorted = $name1Parts;
        $name2PartsSorted = $name2Parts;
        sort($name1PartsSorted);
        sort($name2PartsSorted);
        
        // Check if all words match (order-independent)
        if ($name1PartsSorted === $name2PartsSorted) {
            return 100;
        }
        
        // Check if one name contains all words of the other (handles "JOHN DOE MICHEAL" vs "JOHN DOE")
        $shorterParts = count($name1Parts) <= count($name2Parts) ? $name1PartsSorted : $name2PartsSorted;
        $longerParts = count($name1Parts) > count($name2Parts) ? $name1PartsSorted : $name2PartsSorted;
        
        $allWordsMatch = true;
        $matchedWordsCount = 0;
        foreach ($shorterParts as $word) {
            if (in_array($word, $longerParts)) {
                $matchedWordsCount++;
            } else {
                $allWordsMatch = false;
            }
        }
        
        // If shorter name's words all exist in longer name, give high similarity
        // This handles cases like "JOHN DOE" vs "JOHN DOE MICHEAL" - should be ~90%+
        if ($allWordsMatch && count($shorterParts) >= 2) {
            // If at least 2 words match and all shorter words exist in longer name, give high score
            // Base score: percentage of shorter name words in longer name
            $baseScore = (count($shorterParts) / count($longerParts)) * 100;
            // Boost score if shorter name has 2+ words (more reliable match)
            $boost = count($shorterParts) >= 2 ? 15 : 0;
            $finalScore = min(100, $baseScore + $boost);
            
            \Illuminate\Support\Facades\Log::info('Name similarity: subset match (all words match)', [
                'name1' => $name1,
                'name2' => $name2,
                'shorter_parts' => $shorterParts,
                'longer_parts' => $longerParts,
                'base_score' => $baseScore,
                'final_score' => $finalScore,
            ]);
            
            return (int) round($finalScore);
        }
        
        // If most words from shorter name match (e.g., 2 out of 2, or 2 out of 3), still give good score
        if ($matchedWordsCount >= 2 && count($shorterParts) >= 2) {
            $matchRatio = $matchedWordsCount / count($shorterParts);
            // If 100% of shorter name words match, give very high score
            if ($matchRatio >= 1.0) {
                $baseScore = (count($shorterParts) / count($longerParts)) * 100;
                $finalScore = min(100, $baseScore + 10);
                
                \Illuminate\Support\Facades\Log::info('Name similarity: high word match ratio', [
                    'name1' => $name1,
                    'name2' => $name2,
                    'shorter_parts' => $shorterParts,
                    'longer_parts' => $longerParts,
                    'matched_words' => $matchedWordsCount,
                    'match_ratio' => $matchRatio,
                    'base_score' => $baseScore,
                    'final_score' => $finalScore,
                ]);
                
                return (int) round($finalScore);
            }
        }
        
        // Check word-by-word exact match
        $matchedWords = 0;
        $totalWords = max(count($name1Parts), count($name2Parts));
        
        foreach ($name1Parts as $part1) {
            foreach ($name2Parts as $part2) {
                if ($part1 === $part2) {
                    $matchedWords++;
                    break;
                }
            }
        }
        
        // Calculate word match similarity
        $wordMatchSimilarity = $matchedWords > 0 && $totalWords > 0 
            ? ($matchedWords / $totalWords) * 100 
            : 0;
        
        // If most words match (e.g., 2 out of 3), give higher score
        if ($matchedWords >= 2 && $totalWords >= 2) {
            // If at least 2 words match, boost the score
            $wordMatchSimilarity = max($wordMatchSimilarity, ($matchedWords / $totalWords) * 100 + 20);
        }
        
        // Calculate Levenshtein similarity
        $levenshteinSimilarity = $this->calculateLevenshteinSimilarity($name1, $name2);
        
        // Use the higher of the two, but give more weight to word matching
        $finalSimilarity = max($wordMatchSimilarity * 1.2, $levenshteinSimilarity);
        
        \Illuminate\Support\Facades\Log::info('Name similarity calculation details', [
            'name1' => $name1,
            'name2' => $name2,
            'name1_parts' => $name1Parts,
            'name2_parts' => $name2Parts,
            'matched_words' => $matchedWords,
            'total_words' => $totalWords,
            'word_match_similarity' => $wordMatchSimilarity,
            'levenshtein_similarity' => $levenshteinSimilarity,
            'final_similarity' => $finalSimilarity,
        ]);
        
        return (int) round(min(100, $finalSimilarity));
    }
    
    private function calculateLevenshteinSimilarity(string $name1, string $name2): int
    {
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

    /**
     * Get insurance company registration desk settings
     * Used by kashtre registration desk to determine if policy details should be shown
     */
    public function getInsuranceCompanySettings($id)
    {
        try {
            $insuranceCompany = InsuranceCompany::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'message' => 'Insurance company registration settings retrieved successfully',
                'show_policy_details_at_registration' => $insuranceCompany->show_policy_details_at_registration ?? false,
                'policy_details_to_display_at_registration' => $insuranceCompany->policy_details_to_display_at_registration ?? [],
                'visit_authorization_period_days' => $insuranceCompany->visit_authorization_period_days ?? 7,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Insurance company not found.',
            ], 404);
        }
    }
}
