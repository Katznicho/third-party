<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InsuranceCompany;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
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
                'code' => 'nullable|string|max:50|unique:insurance_companies,code',
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
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $validated = $validator->validated();

            // Generate code and slug if not provided
            $code = $validated['code'] ?? strtoupper(Str::substr(Str::slug($validated['name']), 0, 10));
            $slug = Str::slug($validated['name']);

            // Check if code already exists, if so append random string
            if (InsuranceCompany::where('code', $code)->exists()) {
                $code = $code . '_' . Str::random(4);
            }

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
}
