<?php

namespace App\Http\Controllers;

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
        ]);

        $validated['slug'] = $this->generateUniqueSlug($validated['name']);
        $validated['insurance_company_id'] = auth()->user()->insurance_company_id;
        $validated['is_active'] = $request->boolean('is_active', true);

        $plan = Plan::create($validated);

        // Attach service categories with pivot data
        if ($request->has('service_categories') && is_array($request->service_categories)) {
            $syncData = [];
            foreach ($request->service_categories as $categoryId => $categoryData) {
                if (isset($categoryData['id'])) {
                    $categoryIdValue = $categoryData['id'];
                    $syncData[$categoryIdValue] = [
                        'benefit_amount' => isset($categoryData['benefit_amount']) && $categoryData['benefit_amount'] !== '' ? (float)$categoryData['benefit_amount'] : null,
                        'copay_percentage' => isset($categoryData['copay_percentage']) && $categoryData['copay_percentage'] !== '' ? (float)$categoryData['copay_percentage'] : 0,
                        'copay_type' => isset($categoryData['copay_type']) && in_array($categoryData['copay_type'], ['fixed', 'percentage']) ? $categoryData['copay_type'] : 'percentage',
                        'waiting_period_days' => isset($categoryData['waiting_period_days']) && $categoryData['waiting_period_days'] !== '' ? (int)$categoryData['waiting_period_days'] : 0,
                        'is_enabled' => isset($categoryData['is_enabled']) ? (bool)$categoryData['is_enabled'] : true,
                    ];
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
        ]);

        // Only update slug if name changed
        if ($plan->name !== $validated['name']) {
            $validated['slug'] = $this->generateUniqueSlug($validated['name'], $plan->id);
        }
        $validated['is_active'] = $request->boolean('is_active');

        $plan->update($validated);

        // Sync service categories with pivot data
        if ($request->has('service_categories') && is_array($request->service_categories)) {
            $syncData = [];
            foreach ($request->service_categories as $categoryId => $categoryData) {
                if (isset($categoryData['id'])) {
                    $categoryIdValue = $categoryData['id'];
                    $syncData[$categoryIdValue] = [
                        'benefit_amount' => isset($categoryData['benefit_amount']) && $categoryData['benefit_amount'] !== '' ? (float)$categoryData['benefit_amount'] : null,
                        'copay_percentage' => isset($categoryData['copay_percentage']) && $categoryData['copay_percentage'] !== '' ? (float)$categoryData['copay_percentage'] : 0,
                        'copay_type' => isset($categoryData['copay_type']) && in_array($categoryData['copay_type'], ['fixed', 'percentage']) ? $categoryData['copay_type'] : 'percentage',
                        'waiting_period_days' => isset($categoryData['waiting_period_days']) && $categoryData['waiting_period_days'] !== '' ? (int)$categoryData['waiting_period_days'] : 0,
                        'is_enabled' => isset($categoryData['is_enabled']) ? (bool)$categoryData['is_enabled'] : true,
                    ];
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
