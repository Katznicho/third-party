<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Policy;
use App\Models\PolicyDeductibleLedger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class InvoiceAuthorizationController extends Controller
{
    /**
     * Receive invoice from Kashtre (entity) for authorization.
     * Compute deductible, co-pay, co-insurance and optional exclusions;
     * return client_total and insurance_total.
     */
    public function request(Request $request): JsonResponse
    {
        Log::info('[InsuranceAuth] Request received', [
            'kashtre_invoice_id' => $request->input('kashtre_invoice_id'),
            'invoice_number' => $request->input('invoice_number'),
            'insurance_company_id' => $request->input('insurance_company_id'),
            'policy_number' => $request->input('policy_number'),
            'total_amount' => $request->input('total_amount'),
            'deductible_remaining' => $request->input('deductible_remaining'),
            'items_count' => is_array($request->input('items')) ? count($request->input('items')) : 0,
        ]);

        $validated = $request->validate([
            'kashtre_invoice_id' => 'required|string|max:64',
            'invoice_number' => 'required|string|max:64',
            'insurance_company_id' => 'required|exists:insurance_companies,id',
            'policy_number' => 'required|string|max:64',
            'total_amount' => 'required|numeric|min:0',
            'deductible_remaining' => 'nullable|numeric|min:0',
            'copay_used_this_period' => 'nullable|numeric|min:0',
            'items' => 'nullable|array',
            'items.*.name' => 'nullable|string',
            'items.*.quantity' => 'nullable|numeric',
            'items.*.price' => 'nullable|numeric',
            'items.*.total_amount' => 'nullable|numeric',
        ]);

        $insuranceCompanyId = (int) $validated['insurance_company_id'];
        $policyNumber = trim($validated['policy_number']);
        $totalAmount = (float) $validated['total_amount'];
        $deductibleRemaining = isset($validated['deductible_remaining']) ? (float) $validated['deductible_remaining'] : 0;
        $copayUsedThisPeriod = isset($validated['copay_used_this_period']) ? (float) $validated['copay_used_this_period'] : 0;

        $policy = Policy::where('insurance_company_id', $insuranceCompanyId)
            ->where('policy_number', $policyNumber)
            ->with('insuranceCompany')
            ->first();

        if (!$policy) {
            Log::warning('[InsuranceAuth] Policy not found', [
                'insurance_company_id' => $insuranceCompanyId,
                'policy_number' => $policyNumber,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Policy not found.',
            ], 404);
        }

        Log::info('[InsuranceAuth] Policy found (same data as client Policy Options)', [
            'policy_id' => $policy->id,
            'policy_number' => $policy->policy_number,
            'has_deductible' => $policy->has_deductible,
            'deductible_amount' => $policy->deductible_amount,
            'copay_amount' => $policy->copay_amount,
            'copay_max_limit' => $policy->copay_max_limit,
            'coinsurance_percentage' => $policy->coinsurance_percentage,
            'copay_contributes_to_deductible' => $policy->copayContributesToDeductible(),
            'coinsurance_contributes_to_deductible' => $policy->coinsuranceContributesToDeductible(),
        ]);

        if (!$policy->isActive()) {
            Log::warning('[InsuranceAuth] Policy not active', ['policy_id' => $policy->id]);
            return response()->json([
                'success' => false,
                'message' => 'Policy is not active.',
            ], 422);
        }

        // === NEW AUTHORIZATION LOGIC ===
        // We treat the total_amount from Kashtre as the approved amount for this visit (after coverage rules).
        $approvedAmount = max(0.0, (float) $totalAmount);

        // Policy-level settings
        $deductibleAmountPolicy = $policy->has_deductible ? (float) ($policy->deductible_amount ?? 0) : 0;
        $copayAmount = (float) ($policy->copay_amount ?? 0);
        $copayMaxLimit = $policy->copay_max_limit !== null ? (float) $policy->copay_max_limit : null;
        $coinsurancePct = (float) ($policy->coinsurance_percentage ?? 0);

        // Effective outstanding deductible before this visit:
        // if Kashtre sends a remaining amount, trust it; otherwise fall back to policy deductible.
        if ($policy->has_deductible) {
            $effectiveDeductibleBefore = $deductibleRemaining !== null
                ? max(0.0, (float) $deductibleRemaining)
                : max(0.0, $deductibleAmountPolicy);
        } else {
            $effectiveDeductibleBefore = 0.0;
        }

        // 1) Raw co-pay for this visit (respecting max limit / room left this period)
        $copayCapThisVisit = $copayAmount;
        if ($copayMaxLimit !== null && $copayMaxLimit > 0) {
            $copayRoomLeft = max(0, $copayMaxLimit - $copayUsedThisPeriod);
            $copayCapThisVisit = min($copayAmount, $copayRoomLeft);
        }
        $copayRaw = max(0.0, (float) $copayCapThisVisit);

        // 2) Raw coinsurance based on approved amount
        $coinsuranceRaw = $approvedAmount > 0 && $coinsurancePct > 0
            ? round($approvedAmount * ($coinsurancePct / 100), 2)
            : 0.0;

        // 3) Raw deductible this visit cannot exceed both remaining deductible and approved amount
        $deductibleRaw = 0.0;
        if ($policy->has_deductible && $approvedAmount > 0) {
            $deductibleRaw = min($effectiveDeductibleBefore, $approvedAmount);
        }

        // 4) Allocate client share from the approved amount without exceeding it.
        //    Order: co-pay first, then coinsurance, then deductible (so co-pay is always honoured when possible).
        $remainingForClient = $approvedAmount;

        $copayPortion = min($copayRaw, $remainingForClient);
        $remainingForClient -= $copayPortion;

        $coinsurancePortion = min($coinsuranceRaw, $remainingForClient);
        $remainingForClient -= $coinsurancePortion;

        $deductiblePortion = min($deductibleRaw, $remainingForClient);
        $remainingForClient -= $deductiblePortion;

        // Client total is the sum of the three portions; insurer pays the rest of the approved amount.
        $clientTotal = round($deductiblePortion + $copayPortion + $coinsurancePortion, 2);
        $insuranceTotal = round(max(0, $approvedAmount - $clientTotal), 2);

        // 5) Amount that reduces deductible: deductible this visit + any copay/coinsurance that contribute
        $amountThatReducesDeductible = $deductiblePortion + $policy->calculateDeductibleContribution($copayPortion, $coinsurancePortion);
        $deductibleBefore = $effectiveDeductibleBefore;
        $deductibleAfter = max(0, $deductibleBefore - $amountThatReducesDeductible);

        // Breakdown for UI
        $breakdown = [
            'deductible' => $deductiblePortion,
            'copay' => $copayPortion,
            'coinsurance' => $coinsurancePortion,
            'excluded' => 0,
        ];

        Log::info('[InsuranceAuth] Calculation complete', [
            'approved_amount' => $approvedAmount,
            'deductible_remaining_before' => $deductibleBefore,
            'deductible_portion' => $deductiblePortion,
            'copay_portion' => $copayPortion,
            'coinsurance_portion' => $coinsurancePortion,
            'amount_that_reduces_deductible' => $amountThatReducesDeductible,
            'deductible_remaining_after' => $deductibleAfter,
            'client_total' => $clientTotal,
            'insurance_total' => $insuranceTotal,
            'breakdown' => $breakdown,
        ]);

        $authorizationReference = 'AUTH-' . strtoupper(Str::random(12));
        $confirmationCode = strtoupper(Str::random(6));

        $auth = \App\Models\InsuranceAuthorization::create([
            'insurance_company_id' => $insuranceCompanyId,
            'policy_id' => $policy->id,
            'kashtre_invoice_id' => $validated['kashtre_invoice_id'],
            'external_invoice_number' => $validated['invoice_number'],
            'total_amount' => $approvedAmount,
            'client_total' => $clientTotal,
            'insurance_total' => $insuranceTotal,
            'breakdown' => $breakdown,
            'status' => 'completed',
            'confirmation_code' => $confirmationCode,
            'authorization_reference' => $authorizationReference,
            'requested_at' => now(),
            'completed_at' => now(),
            'metadata' => [
                'items' => $validated['items'] ?? [],
                'deductible_remaining_before' => $deductibleBefore,
                'deductible_remaining_after' => $deductibleAfter,
                'amount_that_reduces_deductible' => $amountThatReducesDeductible,
                'copay_contributes_to_deductible' => $policy->copayContributesToDeductible(),
                'coinsurance_contributes_to_deductible' => $policy->coinsuranceContributesToDeductible(),
            ],
        ]);

        // Create ledger entry so insurer can see how deductible moves over time
        PolicyDeductibleLedger::create([
            'insurance_company_id' => $insuranceCompanyId,
            'policy_id' => $policy->id,
            'authorization_id' => $auth->id,
            'kashtre_invoice_id' => $validated['kashtre_invoice_id'],
            'external_invoice_number' => $validated['invoice_number'],
            'change_type' => 'invoice',
            'deductible_before' => $deductibleBefore,
            'amount_that_reduces_deductible' => $amountThatReducesDeductible,
            'deductible_after' => $deductibleAfter,
            'notes' => null,
        ]);

        Log::info('[InsuranceAuth] Authorization saved and response sent', [
            'authorization_id' => $auth->id,
            'authorization_reference' => $authorizationReference,
            'confirmation_code' => $confirmationCode,
            'client_total' => $clientTotal,
            'insurance_total' => $insuranceTotal,
        ]);

        // Policy options (same as shown on third-party client page /clients/{id}) so Kashtre can display "what this client has to pay"
        $policyOptions = [
            'has_deductible' => (bool) $policy->has_deductible,
            'deductible_amount' => $policy->has_deductible ? (float) ($policy->deductible_amount ?? 0) : null,
            'copay_amount' => $copayAmount > 0 ? (float) $copayAmount : null,
            'copay_max_limit' => $copayMaxLimit,
            'coinsurance_percentage' => $coinsurancePct > 0 ? (float) $coinsurancePct : null,
        ];

        return response()->json([
            'success' => true,
            'authorization_reference' => $authorizationReference,
            'confirmation_code' => $confirmationCode,
            'client_total' => $clientTotal,
            'insurance_total' => $insuranceTotal,
            'breakdown' => $breakdown,
            'policy_options' => $policyOptions,
            'amount_that_reduces_deductible' => round($amountThatReducesDeductible, 2),
            'copay_contributes_to_deductible' => $policy->copayContributesToDeductible(),
            'coinsurance_contributes_to_deductible' => $policy->coinsuranceContributesToDeductible(),
        ]);
    }
}
