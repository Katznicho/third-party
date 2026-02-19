<?php

namespace App\Http\Controllers;

use App\Models\PreAuthorization;
use App\Models\AuthorizationAuditLog;
use App\Services\AuthorizationRuleEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AuthorizationReviewController extends Controller
{
    protected $authService;

    public function __construct(\App\Services\SimpleAuthorizationService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Display the review queue (flagged for review)
     */
    public function index()
    {
        $insuranceCompanyId = auth()->user()->insurance_company_id;
        
        $flaggedPreAuthorizations = PreAuthorization::with(['policy', 'client', 'serviceCategory'])
            ->whereHas('policy', function($query) use ($insuranceCompanyId) {
                $query->where('insurance_company_id', $insuranceCompanyId);
            })
            ->where('status', 'pending')
            ->where(function($query) {
                $query->where('authorization_method', 'automatic')
                    ->orWhereNull('authorization_method');
            })
            ->latest()
            ->paginate(20);
        
        return view('authorization-review.index', compact('flaggedPreAuthorizations'));
    }

    /**
     * Show a specific pre-authorization for review
     */
    public function show(PreAuthorization $preAuthorization)
    {
        $insuranceCompanyId = auth()->user()->insurance_company_id;
        
        // Ensure user can only review their own company's pre-authorizations
        if ($preAuthorization->policy->insurance_company_id !== $insuranceCompanyId) {
            abort(403, 'Unauthorized');
        }
        
        // Get audit logs for this pre-authorization
        $auditLogs = AuthorizationAuditLog::where('pre_authorization_id', $preAuthorization->id)
            ->latest()
            ->get();
        
        $preAuthorization->load(['policy', 'client', 'serviceCategory', 'items']);
        
        return view('authorization-review.show', compact('preAuthorization', 'auditLogs'));
    }

    /**
     * Manually approve a pre-authorization
     */
    public function approve(Request $request, PreAuthorization $preAuthorization)
    {
        $insuranceCompanyId = auth()->user()->insurance_company_id;
        
        // Ensure user can only approve their own company's pre-authorizations
        if ($preAuthorization->policy->insurance_company_id !== $insuranceCompanyId) {
            abort(403, 'Unauthorized');
        }
        
        $validated = $request->validate([
            'approved_amount' => 'nullable|numeric|min:0|max:' . $preAuthorization->requested_amount,
            'notes' => 'nullable|string|max:1000',
        ]);

        $approvedAmount = $validated['approved_amount'] ?? $preAuthorization->requested_amount;
        
        if ($this->authService->manuallyApprove($preAuthorization, $approvedAmount, $validated['notes'] ?? null)) {
            return redirect()->back()
                ->with('success', 'Pre-authorization approved successfully.');
        }
        
        return back()->with('error', 'Failed to approve pre-authorization.');
    }

    /**
     * Manually reject a pre-authorization
     */
    public function reject(Request $request, PreAuthorization $preAuthorization)
    {
        $insuranceCompanyId = auth()->user()->insurance_company_id;
        
        // Ensure user can only reject their own company's pre-authorizations
        if ($preAuthorization->policy->insurance_company_id !== $insuranceCompanyId) {
            abort(403, 'Unauthorized');
        }
        
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        if ($this->authService->manuallyReject($preAuthorization, $validated['rejection_reason'])) {
            return redirect()->back()
                ->with('success', 'Pre-authorization rejected successfully.');
        }
        
        return back()->with('error', 'Failed to reject pre-authorization.');
    }

    /**
     * Re-process a pre-authorization through the rules engine
     */
    public function reprocess(PreAuthorization $preAuthorization)
    {
        $insuranceCompanyId = auth()->user()->insurance_company_id;
        
        // Ensure user can only reprocess their own company's pre-authorizations
        if ($preAuthorization->policy->insurance_company_id !== $insuranceCompanyId) {
            abort(403, 'Unauthorized');
        }

        try {
            $result = $this->authService->process($preAuthorization);
            
            // Update pre-authorization based on result
            $status = match($result['decision']) {
                'auto_approved' => 'approved',
                'auto_rejected' => 'rejected',
                default => 'pending',
            };

            if ($status === 'approved') {
                $preAuthorization->update([
                    'status' => 'approved',
                    'approved_amount' => $result['approved_amount'],
                    'approval_date' => now(),
                    'authorization_method' => 'automatic',
                ]);
                if (!$preAuthorization->approval_id) {
                    $preAuthorization->generateApprovalId();
                }
            } elseif ($status === 'rejected') {
                $preAuthorization->update([
                    'status' => 'rejected',
                    'approved_amount' => 0,
                    'rejection_reason' => $result['reason'] ?? 'Automatically rejected',
                    'authorization_method' => 'automatic',
                ]);
            } else {
                $preAuthorization->update([
                    'status' => 'pending',
                    'authorization_method' => 'automatic',
                ]);
            }

            Log::info('Pre-authorization reprocessed', [
                'pre_authorization_id' => $preAuthorization->id,
                'decision' => $result['decision'],
            ]);

            return redirect()->route('authorization-review.show', $preAuthorization)
                ->with('success', 'Pre-authorization reprocessed. Decision: ' . $result['decision']);
                
        } catch (\Exception $e) {
            Log::error('Failed to reprocess pre-authorization', [
                'pre_authorization_id' => $preAuthorization->id,
                'error' => $e->getMessage(),
            ]);
            
            return back()->with('error', 'Failed to reprocess: ' . $e->getMessage());
        }
    }
}
