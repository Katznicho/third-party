<?php

namespace App\Http\Controllers;

use App\Models\ConnectedCompanyItemCoverage;
use App\Models\Plan;
use App\Models\ServiceCategory;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $insuranceCompanyId = auth()->user()->insurance_company_id;
        
        $plans = Plan::with(['insuranceCompany', 'serviceCategories'])
            ->where('insurance_company_id', $insuranceCompanyId)
            ->orderBy('sort_order')
            ->latest()
            ->paginate(15);
            
        return view('plans.index', compact('plans'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $serviceCategories = ServiceCategory::where('is_active', true)->orderBy('sort_order')->get();
        return view('plans.create', compact('serviceCategories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => [
                'required',
                'string',
                'max:50',
                \Illuminate\Validation\Rule::unique('plans')->where(function ($query) {
                    return $query->where('insurance_company_id', auth()->user()->insurance_company_id);
                }),
            ],
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
            // Age Limits
            'min_enrollment_age' => 'nullable|integer|min:0|max:120',
            'max_enrollment_age' => 'nullable|integer|min:0|max:120|gte:min_enrollment_age',
            // Dependent Coverage
            'dependent_coverage_multiplier' => 'nullable|numeric|min:0|max:2',
            // Coverage Limits
            'annual_max_coverage' => 'nullable|numeric|min:0',
            'lifetime_max_coverage' => 'nullable|numeric|min:0',
            'per_incident_max_coverage' => 'nullable|numeric|min:0',
            // Plan Image
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            // Terms & Conditions
            'terms_and_conditions' => 'nullable|string',
            'terms_link' => 'nullable|url|max:500',
            // Premium Calculation
            'base_premium' => 'nullable|numeric|min:0',
            'premium_calculation_method' => 'nullable|in:benefit_based,fixed,hybrid',
            'insurance_training_levy_percentage' => 'nullable|numeric|min:0|max:100',
            'stamp_duty_amount' => 'nullable|numeric|min:0',
        ]);

        $validated['slug'] = $this->generateUniqueSlug($validated['name']);
        $validated['insurance_company_id'] = auth()->user()->insurance_company_id;
        $validated['is_active'] = $request->boolean('is_active', true);
        
        // Set default values if not provided
        $validated['dependent_coverage_multiplier'] = $validated['dependent_coverage_multiplier'] ?? 0.50;
        $validated['premium_calculation_method'] = $validated['premium_calculation_method'] ?? 'benefit_based';
        $validated['insurance_training_levy_percentage'] = $validated['insurance_training_levy_percentage'] ?? 0.50;
        $validated['stamp_duty_amount'] = $validated['stamp_duty_amount'] ?? 35000;
        
        // Build tiered multipliers array from individual tier inputs
        $tiers = [];
        if (isset($validated['dependent_multiplier_tier_1'])) {
            $tiers[] = $validated['dependent_multiplier_tier_1'];
        }
        if (isset($validated['dependent_multiplier_tier_2'])) {
            $tiers[] = $validated['dependent_multiplier_tier_2'];
        }
        if (isset($validated['dependent_multiplier_tier_3'])) {
            $tiers[] = $validated['dependent_multiplier_tier_3'];
        }
        $validated['dependent_multiplier_tiers'] = !empty($tiers) ? $tiers : null;
        
        // Remove individual tier fields from validated (they're now in the array)
        unset($validated['dependent_multiplier_tier_1'], $validated['dependent_multiplier_tier_2'], $validated['dependent_multiplier_tier_3']);
        
        // Handle image upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $imagePath = $image->storeAs('plans', $imageName, 'public');
            $validated['image_path'] = $imagePath;
        }

        $plan = Plan::create($validated);

        // Attach service categories with pivot data
        if ($request->has('service_categories') && is_array($request->service_categories)) {
            $syncData = [];
            foreach ($request->service_categories as $categoryId => $categoryData) {
                if (isset($categoryData['id'])) {
                    $categoryIdValue = $categoryData['id'];
                    $syncData[$categoryIdValue] = $this->pivotDataForServiceCategory($categoryData);
                }
            }
            $plan->serviceCategories()->sync($syncData);
        }

        return redirect()->route('plans.index')
            ->with('success', 'Plan created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Plan $plan)
    {
        // Ensure user can only view plans from their insurance company
        if ($plan->insurance_company_id !== auth()->user()->insurance_company_id) {
            abort(403, 'Unauthorized access to plan.');
        }

        $plan->load(['insuranceCompany', 'serviceCategories', 'clients']);
        return view('plans.show', compact('plan'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Plan $plan)
    {
        // Ensure user can only edit plans from their insurance company
        if ($plan->insurance_company_id !== auth()->user()->insurance_company_id) {
            abort(403, 'Unauthorized access to plan.');
        }

        $serviceCategories = ServiceCategory::where('is_active', true)->orderBy('sort_order')->get();
        $plan->load('serviceCategories');
        return view('plans.edit', compact('plan', 'serviceCategories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Plan $plan)
    {
        // Ensure user can only update plans from their insurance company
        if ($plan->insurance_company_id !== auth()->user()->insurance_company_id) {
            abort(403, 'Unauthorized access to plan.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => [
                'required',
                'string',
                'max:50',
                \Illuminate\Validation\Rule::unique('plans')->where(function ($query) use ($plan) {
                    return $query->where('insurance_company_id', auth()->user()->insurance_company_id)
                                  ->where('id', '!=', $plan->id);
                }),
            ],
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
            // Age Limits
            'min_enrollment_age' => 'nullable|integer|min:0|max:120',
            'max_enrollment_age' => 'nullable|integer|min:0|max:120|gte:min_enrollment_age',
            // Dependent Coverage
            'dependent_coverage_multiplier' => 'nullable|numeric|min:0|max:2',
            // Coverage Limits
            'annual_max_coverage' => 'nullable|numeric|min:0',
            'lifetime_max_coverage' => 'nullable|numeric|min:0',
            'per_incident_max_coverage' => 'nullable|numeric|min:0',
            // Plan Image
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            // Terms & Conditions
            'terms_and_conditions' => 'nullable|string',
            'terms_link' => 'nullable|url|max:500',
            // Premium Calculation
            'base_premium' => 'nullable|numeric|min:0',
            'premium_calculation_method' => 'nullable|in:benefit_based,fixed,hybrid',
            'insurance_training_levy_percentage' => 'nullable|numeric|min:0|max:100',
            'stamp_duty_amount' => 'nullable|numeric|min:0',
        ]);

        // Only update slug if name changed
        if ($plan->name !== $validated['name']) {
            $validated['slug'] = $this->generateUniqueSlug($validated['name'], $plan->id);
        }
        $validated['is_active'] = $request->boolean('is_active');
        
        // Build tiered multipliers array from individual tier inputs
        $tiers = [];
        if (isset($validated['dependent_multiplier_tier_1'])) {
            $tiers[] = $validated['dependent_multiplier_tier_1'];
        }
        if (isset($validated['dependent_multiplier_tier_2'])) {
            $tiers[] = $validated['dependent_multiplier_tier_2'];
        }
        if (isset($validated['dependent_multiplier_tier_3'])) {
            $tiers[] = $validated['dependent_multiplier_tier_3'];
        }
        $validated['dependent_multiplier_tiers'] = !empty($tiers) ? $tiers : null;
        
        // Remove individual tier fields from validated (they're now in the array)
        unset($validated['dependent_multiplier_tier_1'], $validated['dependent_multiplier_tier_2'], $validated['dependent_multiplier_tier_3']);
        
        // Set default values if not provided
        $validated['dependent_coverage_multiplier'] = $validated['dependent_coverage_multiplier'] ?? $plan->dependent_coverage_multiplier ?? 0.50;
        $validated['premium_calculation_method'] = $validated['premium_calculation_method'] ?? $plan->premium_calculation_method ?? 'benefit_based';
        $validated['insurance_training_levy_percentage'] = $validated['insurance_training_levy_percentage'] ?? $plan->insurance_training_levy_percentage ?? 0.50;
        $validated['stamp_duty_amount'] = $validated['stamp_duty_amount'] ?? $plan->stamp_duty_amount ?? 35000;
        
        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($plan->image_path && \Storage::disk('public')->exists($plan->image_path)) {
                \Storage::disk('public')->delete($plan->image_path);
            }
            
            $image = $request->file('image');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $imagePath = $image->storeAs('plans', $imageName, 'public');
            $validated['image_path'] = $imagePath;
        }

        $plan->update($validated);

        // Sync service categories with pivot data
        if ($request->has('service_categories') && is_array($request->service_categories)) {
            $syncData = [];
            foreach ($request->service_categories as $categoryId => $categoryData) {
                if (isset($categoryData['id'])) {
                    $categoryIdValue = $categoryData['id'];
                    $syncData[$categoryIdValue] = $this->pivotDataForServiceCategory($categoryData);
                }
            }
            $plan->serviceCategories()->sync($syncData);
        } else {
            // If no service categories provided, detach all
            $plan->serviceCategories()->sync([]);
        }

        return redirect()->route('plans.index')
            ->with('success', 'Plan updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Plan $plan)
    {
        // Ensure user can only delete plans from their insurance company
        if ($plan->insurance_company_id !== auth()->user()->insurance_company_id) {
            abort(403, 'Unauthorized access to plan.');
        }

        // Check if plan has related records
        if ($plan->clients()->count() > 0) {
            return redirect()->route('plans.index')
                ->with('error', 'Cannot delete plan with existing clients. Please reassign clients to another plan first.');
        }

        $plan->delete();

        return redirect()->route('plans.index')
            ->with('success', 'Plan deleted successfully.');
    }

    /**
     * Get plan benefits for API
     */
    public function getBenefits($id)
    {
        try {
            $plan = Plan::with('serviceCategories')->findOrFail($id);
            
            $benefits = $plan->serviceCategories->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'code' => $category->code,
                    'amount' => $category->pivot->benefit_amount ?? 0,
                    'coverage_percent' => (float) ($category->pivot->coverage_percent ?? 100),
                    'is_enabled' => $category->pivot->is_enabled ?? false,
                ];
            })->filter(function ($benefit) {
                return $benefit['is_enabled'] && $benefit['amount'] > 0;
            })->values();

            return response()->json([
                'success' => true,
                'benefits' => $benefits,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Plan not found',
            ], 404);
        }
    }

    /**
     * @param  array<string, mixed>  $categoryData
     * @return array<string, mixed>
     */
    private function pivotDataForServiceCategory(array $categoryData): array
    {
        return [
            'benefit_amount' => isset($categoryData['benefit_amount']) && $categoryData['benefit_amount'] !== ''
                ? (float) $categoryData['benefit_amount']
                : null,
            'base_amount' => isset($categoryData['base_amount']) && $categoryData['base_amount'] !== ''
                ? (float) $categoryData['base_amount']
                : null,
            'coverage_percent' => ConnectedCompanyItemCoverage::normalizePercent(
                (float) ($categoryData['coverage_percent'] ?? 100)
            ),
            'waiting_period_days' => isset($categoryData['waiting_period_days']) && $categoryData['waiting_period_days'] !== ''
                ? (int) $categoryData['waiting_period_days']
                : 0,
            'is_enabled' => isset($categoryData['is_enabled']) ? (bool) $categoryData['is_enabled'] : true,
        ];
    }

    /**
     * Generate a unique slug for a plan (unique per insurance company)
     */
    private function generateUniqueSlug(string $name, ?int $excludeId = null): string
    {
        $baseSlug = \Illuminate\Support\Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;
        $insuranceCompanyId = auth()->user()->insurance_company_id;

        while (Plan::where('slug', $slug)
            ->where('insurance_company_id', $insuranceCompanyId)
            ->when($excludeId, function ($query) use ($excludeId) {
                return $query->where('id', '!=', $excludeId);
            })
            ->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
