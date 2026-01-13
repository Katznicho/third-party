<?php

namespace App\Http\Controllers;

use App\Models\ServiceCategory;
use Illuminate\Http\Request;

class ServiceCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = ServiceCategory::withCount('plans')
            ->with('plans')
            ->orderBy('sort_order')
            ->latest()
            ->paginate(15);
            
        return view('service-categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('service-categories.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:service_categories,name',
            'code' => 'required|string|max:50|unique:service_categories,code',
            'description' => 'nullable|string',
            'is_mandatory' => 'nullable|boolean',
            'requires_maternity_wait' => 'nullable|boolean',
            'requires_optical_dental_pair' => 'nullable|boolean',
            'waiting_period_days' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $validated['slug'] = \Illuminate\Support\Str::slug($validated['name']);
        $validated['is_mandatory'] = $request->boolean('is_mandatory');
        $validated['requires_maternity_wait'] = $request->boolean('requires_maternity_wait');
        $validated['requires_optical_dental_pair'] = $request->boolean('requires_optical_dental_pair');
        $validated['is_active'] = $request->boolean('is_active', true);

        $category = ServiceCategory::create($validated);

        return redirect()->route('service-categories.index')
            ->with('success', 'Service category created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ServiceCategory $serviceCategory)
    {
        return view('service-categories.show', compact('serviceCategory'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ServiceCategory $serviceCategory)
    {
        return view('service-categories.edit', compact('serviceCategory'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ServiceCategory $serviceCategory)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:service_categories,name,' . $serviceCategory->id,
            'code' => 'required|string|max:50|unique:service_categories,code,' . $serviceCategory->id,
            'description' => 'nullable|string',
            'is_mandatory' => 'nullable|boolean',
            'requires_maternity_wait' => 'nullable|boolean',
            'requires_optical_dental_pair' => 'nullable|boolean',
            'waiting_period_days' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $validated['slug'] = \Illuminate\Support\Str::slug($validated['name']);
        $validated['is_mandatory'] = $request->boolean('is_mandatory');
        $validated['requires_maternity_wait'] = $request->boolean('requires_maternity_wait');
        $validated['requires_optical_dental_pair'] = $request->boolean('requires_optical_dental_pair');
        $validated['is_active'] = $request->boolean('is_active');

        $serviceCategory->update($validated);

        return redirect()->route('service-categories.index')
            ->with('success', 'Service category updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ServiceCategory $serviceCategory)
    {
        // Check if category has related records
        if ($serviceCategory->policyBenefits()->count() > 0 || $serviceCategory->preAuthorizations()->count() > 0) {
            return redirect()->route('service-categories.index')
                ->with('error', 'Cannot delete service category with existing policy benefits or pre-authorizations.');
        }

        $serviceCategory->delete();

        return redirect()->route('service-categories.index')
            ->with('success', 'Service category deleted successfully.');
    }
}
