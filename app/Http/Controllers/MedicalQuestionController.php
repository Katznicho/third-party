<?php

namespace App\Http\Controllers;

use App\Models\MedicalQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MedicalQuestionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $questions = MedicalQuestion::orderBy('order')->orderBy('id')->get();
        return view('medical-questions.index', compact('questions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('medical-questions.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'question_text' => 'required|string|max:1000',
            'question_type' => 'required|in:yes_no,text,date,number',
            'has_exclusion_list' => 'nullable',
            'exclusion_keywords' => 'nullable|string', // Comma-separated keywords
            'requires_additional_info' => 'nullable',
            'additional_info_type' => 'nullable|in:text,date,table',
            'additional_info_label' => 'nullable|string|max:255',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'nullable',
        ]);

        // Handle boolean checkboxes
        $validated['has_exclusion_list'] = $request->boolean('has_exclusion_list');
        $validated['requires_additional_info'] = $request->boolean('requires_additional_info');
        $validated['is_active'] = $request->boolean('is_active', true);

        // Convert comma-separated keywords to array
        if (!empty($validated['exclusion_keywords'])) {
            $keywords = array_map('trim', explode(',', $validated['exclusion_keywords']));
            $validated['exclusion_keywords'] = array_filter($keywords);
        } else {
            $validated['exclusion_keywords'] = [];
        }

        // Set default order if not provided
        if (!isset($validated['order'])) {
            $maxOrder = MedicalQuestion::max('order');
            $validated['order'] = ($maxOrder ?? 0) + 1;
        }

        MedicalQuestion::create($validated);

        return redirect()->route('medical-questions.index')
            ->with('success', 'Medical question created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(MedicalQuestion $medicalQuestion)
    {
        return view('medical-questions.show', compact('medicalQuestion'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MedicalQuestion $medicalQuestion)
    {
        return view('medical-questions.edit', compact('medicalQuestion'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MedicalQuestion $medicalQuestion)
    {
        $validated = $request->validate([
            'question_text' => 'required|string|max:1000',
            'question_type' => 'required|in:yes_no,text,date,number',
            'has_exclusion_list' => 'nullable',
            'exclusion_keywords' => 'nullable|string', // Comma-separated keywords
            'requires_additional_info' => 'nullable',
            'additional_info_type' => 'nullable|in:text,date,table',
            'additional_info_label' => 'nullable|string|max:255',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'nullable',
        ]);

        // Handle boolean checkboxes
        $validated['has_exclusion_list'] = $request->boolean('has_exclusion_list');
        $validated['requires_additional_info'] = $request->boolean('requires_additional_info');
        $validated['is_active'] = $request->boolean('is_active', true);

        // Convert comma-separated keywords to array
        if (!empty($validated['exclusion_keywords'])) {
            $keywords = array_map('trim', explode(',', $validated['exclusion_keywords']));
            $validated['exclusion_keywords'] = array_filter($keywords);
        } else {
            $validated['exclusion_keywords'] = [];
        }

        $medicalQuestion->update($validated);

        return redirect()->route('medical-questions.index')
            ->with('success', 'Medical question updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MedicalQuestion $medicalQuestion)
    {
        $medicalQuestion->delete();

        return redirect()->route('medical-questions.index')
            ->with('success', 'Medical question deleted successfully.');
    }

    /**
     * Update question order (for drag and drop reordering)
     */
    public function updateOrder(Request $request)
    {
        $request->validate([
            'questions' => 'required|array',
            'questions.*.id' => 'required|exists:medical_questions,id',
            'questions.*.order' => 'required|integer',
        ]);

        foreach ($request->questions as $questionData) {
            MedicalQuestion::where('id', $questionData['id'])
                ->update(['order' => $questionData['order']]);
        }

        return response()->json(['success' => true]);
    }
}
