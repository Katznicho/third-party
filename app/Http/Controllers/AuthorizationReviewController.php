<?php

namespace App\Http\Controllers;

use App\Models\PreAuthorization;
use App\Models\PreAuthorizationApproval;
use App\Models\PreAuthorizationApprover;
use App\Models\AuthorizationAuditLog;
use App\Services\SimpleAuthorizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AuthorizationReviewController extends Controller
{
    protected $authService;

    public function __construct(SimpleAuthorizationService $authService)
    {
        $this->authService = $authService;
    }

    public function index(Request $request)
    {
        $insuranceCompany = auth()->user()->insuranceCompany;
        $insuranceCompanyId = $insuranceCompany->id;
        $totalLevels = (int) ($insuranceCompany->invoice_authorization_levels ?? 1);

        $status = $request->get('status', 'pending');
        $search = $request->get('search');

        $query = PreAuthorization::with(['policy', 'client', 'serviceCategory', 'authorizationApprovals.user'])
            ->whereHas('policy', fn($q) => $q->where('insurance_company_id', $insuranceCompanyId));

        if ($status === 'pending') {
            $query->where('status', 'pending');
        } elseif ($status === 'approved') {
            $query->where('status', 'approved');
        } elseif ($status === 'rejected') {
            $query->where('status', 'rejected');
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('authorization_number', 'like', "%{$search}%")
                  ->orWhereHas('client', fn($cq) => $cq->where('first_name', 'like', "%{$search}%")->orWhere('surname', 'like', "%{$search}%"))
                  ->orWhereHas('policy', fn($pq) => $pq->where('policy_number', 'like', "%{$search}%"));
            });
        }

        $flaggedPreAuthorizations = $query->latest()->paginate(20)->withQueryString();

        $counts = [
            'pending' => PreAuthorization::whereHas('policy', fn($q) => $q->where('insurance_company_id', $insuranceCompanyId))->where('status', 'pending')->count(),
            'approved' => PreAuthorization::whereHas('policy', fn($q) => $q->where('insurance_company_id', $insuranceCompanyId))->where('status', 'approved')->count(),
            'rejected' => PreAuthorization::whereHas('policy', fn($q) => $q->where('insurance_company_id', $insuranceCompanyId))->where('status', 'rejected')->count(),
        ];

        $userApproverLevels = PreAuthorizationApprover::where('insurance_company_id', $insuranceCompanyId)
            ->where('user_id', auth()->id())
            ->pluck('level')
            ->toArray();

        $approversByLevel = PreAuthorizationApprover::where('insurance_company_id', $insuranceCompanyId)
            ->with('user')
            ->get()
            ->groupBy('level');

        return view('authorization-review.index', compact(
            'flaggedPreAuthorizations', 'status', 'search', 'counts',
            'totalLevels', 'userApproverLevels', 'approversByLevel'
        ));
    }

    public function show(PreAuthorization $preAuthorization)
    {
        $insuranceCompany = auth()->user()->insuranceCompany;
        $insuranceCompanyId = $insuranceCompany->id;

        if ($preAuthorization->policy->insurance_company_id !== $insuranceCompanyId) {
            abort(403, 'Unauthorized');
        }

        $auditLogs = AuthorizationAuditLog::where('pre_authorization_id', $preAuthorization->id)
            ->latest()
            ->get();

        $preAuthorization->load(['policy', 'client', 'serviceCategory', 'items', 'authorizationApprovals.user']);

        $totalLevels = (int) ($insuranceCompany->invoice_authorization_levels ?? 1);
        $nextLevel = $this->nextPendingLevel($preAuthorization, $totalLevels);
        $userApproverLevels = PreAuthorizationApprover::where('insurance_company_id', $insuranceCompanyId)
            ->where('user_id', auth()->id())
            ->pluck('level')
            ->toArray();

        $approversByLevel = PreAuthorizationApprover::where('insurance_company_id', $insuranceCompanyId)
            ->with('user')
            ->get()
            ->groupBy('level');

        // Look up the InsuranceAuthorization linked to this pre-auth (same policy, pending_review, matching invoice number)
        $insuranceAuth = null;
        $invoiceNumber = null;
        if (preg_match('/Invoice\s+(\S+)/', $preAuthorization->request_description ?? '', $m)) {
            $invoiceNumber = $m[1];
            $insuranceAuth = \App\Models\InsuranceAuthorization::where('policy_id', $preAuthorization->policy_id)
                ->where('external_invoice_number', $invoiceNumber)
                ->first();
        }
        if (!$insuranceAuth) {
            $insuranceAuth = \App\Models\InsuranceAuthorization::where('policy_id', $preAuthorization->policy_id)
                ->where('status', 'pending_review')
                ->latest()
                ->first();
        }

        return view('authorization-review.show', compact(
            'preAuthorization', 'auditLogs', 'totalLevels', 'nextLevel', 'userApproverLevels',
            'approversByLevel', 'insuranceAuth', 'invoiceNumber'
        ));
    }

    public function approve(Request $request, PreAuthorization $preAuthorization)
    {
        $insuranceCompany = auth()->user()->insuranceCompany;
        $insuranceCompanyId = $insuranceCompany->id;

        if ($preAuthorization->policy->insurance_company_id !== $insuranceCompanyId) {
            abort(403, 'Unauthorized');
        }

        $totalLevels = (int) ($insuranceCompany->invoice_authorization_levels ?? 1);

        if ($totalLevels <= 1) {
            $validated = $request->validate([
                'approved_amount' => 'nullable|numeric|min:0|max:' . $preAuthorization->requested_amount,
                'notes' => 'nullable|string|max:1000',
            ]);
            $approvedAmount = $validated['approved_amount'] ?? $preAuthorization->requested_amount;
            if ($this->authService->manuallyApprove($preAuthorization, $approvedAmount, $validated['notes'] ?? null)) {
                return redirect()->back()->with('success', 'Pre-authorization approved.');
            }
            return back()->with('error', 'Failed to approve.');
        }

        $nextLevel = $this->nextPendingLevel($preAuthorization, $totalLevels);
        if (!$nextLevel) {
            return back()->with('error', 'All levels already approved.');
        }

        $isApprover = PreAuthorizationApprover::where('insurance_company_id', $insuranceCompanyId)
            ->where('user_id', auth()->id())
            ->where('level', $nextLevel)
            ->exists();
        if (!$isApprover) {
            return back()->with('error', 'You are not an approver for level ' . $nextLevel . '.');
        }

        $validated = $request->validate([
            'approved_amount' => 'nullable|numeric|min:0|max:' . $preAuthorization->requested_amount,
            'notes' => 'nullable|string|max:1000',
        ]);

        PreAuthorizationApproval::create([
            'pre_authorization_id' => $preAuthorization->id,
            'level' => $nextLevel,
            'user_id' => auth()->id(),
            'action' => 'approved',
            'notes' => $validated['notes'] ?? null,
            'acted_at' => now(),
        ]);

        $allApproved = $preAuthorization->authorizationApprovals()
            ->where('action', 'approved')
            ->distinct('level')
            ->count('level') >= $totalLevels;

        if ($allApproved) {
            $approvedAmount = $validated['approved_amount'] ?? $preAuthorization->requested_amount;
            $this->authService->manuallyApprove($preAuthorization, $approvedAmount, 'Multi-level approval completed');
            return redirect()->back()->with('success', 'Final approval granted. Pre-authorization approved.');
        }

        return redirect()->back()->with('success', 'Level ' . $nextLevel . ' approval recorded. Awaiting level ' . ($nextLevel + 1) . '.');
    }

    public function reject(Request $request, PreAuthorization $preAuthorization)
    {
        $insuranceCompany = auth()->user()->insuranceCompany;
        $insuranceCompanyId = $insuranceCompany->id;

        if ($preAuthorization->policy->insurance_company_id !== $insuranceCompanyId) {
            abort(403, 'Unauthorized');
        }

        $totalLevels = (int) ($insuranceCompany->invoice_authorization_levels ?? 1);

        if ($totalLevels <= 1) {
            $validated = $request->validate([
                'rejection_reason' => 'required|string|max:1000',
            ]);
            if ($this->authService->manuallyReject($preAuthorization, $validated['rejection_reason'])) {
                return redirect()->back()->with('success', 'Pre-authorization rejected.');
            }
            return back()->with('error', 'Failed to reject.');
        }

        $nextLevel = $this->nextPendingLevel($preAuthorization, $totalLevels);
        if (!$nextLevel) {
            return back()->with('error', 'Cannot reject — no pending level.');
        }

        $isApprover = PreAuthorizationApprover::where('insurance_company_id', $insuranceCompanyId)
            ->where('user_id', auth()->id())
            ->where('level', $nextLevel)
            ->exists();
        if (!$isApprover) {
            return back()->with('error', 'You are not an approver for level ' . $nextLevel . '.');
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        PreAuthorizationApproval::create([
            'pre_authorization_id' => $preAuthorization->id,
            'level' => $nextLevel,
            'user_id' => auth()->id(),
            'action' => 'rejected',
            'notes' => $validated['rejection_reason'],
            'acted_at' => now(),
        ]);

        $this->authService->manuallyReject($preAuthorization, $validated['rejection_reason']);

        return redirect()->back()->with('success', 'Pre-authorization rejected at level ' . $nextLevel . '.');
    }

    public function reprocess(PreAuthorization $preAuthorization)
    {
        $insuranceCompanyId = auth()->user()->insurance_company_id;

        if ($preAuthorization->policy->insurance_company_id !== $insuranceCompanyId) {
            abort(403, 'Unauthorized');
        }

        try {
            $result = $this->authService->process($preAuthorization);

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
                ->with('success', 'Re-processed. Decision: ' . $result['decision']);

        } catch (\Exception $e) {
            Log::error('Failed to reprocess pre-authorization', [
                'pre_authorization_id' => $preAuthorization->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to reprocess: ' . $e->getMessage());
        }
    }

    private function nextPendingLevel(PreAuthorization $preAuthorization, int $totalLevels): ?int
    {
        $approvedLevels = $preAuthorization->authorizationApprovals()
            ->where('action', 'approved')
            ->pluck('level')
            ->toArray();

        for ($lvl = 1; $lvl <= $totalLevels; $lvl++) {
            if (!in_array($lvl, $approvedLevels)) {
                return $lvl;
            }
        }
        return null;
    }
}
