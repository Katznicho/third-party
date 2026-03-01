<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InsuranceCompany;
use App\Models\Policy;
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

        // Amount to collect = deductible (policy amount) + co-pay (policy amount per visit) + 10% of invoice. Leave the invoice alone.
        $deductibleAmount = $policy->has_deductible ? (float) ($policy->deductible_amount ?? 0) : 0;
        $copayAmount = (float) ($policy->copay_amount ?? 0);
        $copayMaxLimit = $policy->copay_max_limit !== null ? (float) $policy->copay_max_limit : null;
        $coinsurancePct = (float) ($policy->coinsurance_percentage ?? 0);

        // 1) Deductible = policy deductible amount (e.g. 100,000)
        $deductiblePortion = $deductibleAmount;

        // 2) Co-pay = policy co-pay per visit (e.g. 20,000), respecting max limit if provided
        $copayCapThisVisit = $copayAmount;
        if ($copayMaxLimit !== null && $copayMaxLimit > 0) {
            $copayRoomLeft = max(0, $copayMaxLimit - $copayUsedThisPeriod);
            $copayCapThisVisit = min($copayAmount, $copayRoomLeft);
        }
        $copayPortion = $copayCapThisVisit;

        // 3) Coinsurance = policy % of invoice (e.g. 10% of invoice)
        $coinsurancePortion = $totalAmount > 0 && $coinsurancePct > 0
            ? round($totalAmount * ($coinsurancePct / 100), 2)
            : 0;

        // Total to collect from client = deductible + co-pay + 10% of invoice (invoice left alone)
        $clientTotal = round($deductiblePortion + $copayPortion + $coinsurancePortion, 2);
        // Insurance pays the remainder of the invoice (e.g. 90% when client coinsurance is 10%)
        $insuranceTotal = round(max(0, $totalAmount - $coinsurancePortion), 2);

        // Amount that reduces deductible remaining (for Kashtre to track): deductible + optionally copay/coinsurance per settings
        $amountThatReducesDeductible = $deductiblePortion + $policy->calculateDeductibleContribution($copayPortion, $coinsurancePortion);

        $breakdown = [
            'deductible' => $deductiblePortion,
            'copay' => $copayPortion,
            'coinsurance' => $coinsurancePortion,
            'excluded' => 0,
        ];

        Log::info('[InsuranceAuth] Calculation complete', [
            'total_amount' => $totalAmount,
            'deductible_remaining_sent' => $deductibleRemaining,
            'deductible_portion' => $deductiblePortion,
            'copay_portion' => $copayPortion,
            'coinsurance_portion' => $coinsurancePortion,
            'amount_that_reduces_deductible' => $amountThatReducesDeductible,
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
            'total_amount' => $totalAmount,
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
                'deductible_remaining_sent' => $deductibleRemaining,
                'amount_that_reduces_deductible' => $amountThatReducesDeductible,
                'copay_contributes_to_deductible' => $policy->copayContributesToDeductible(),
                'coinsurance_contributes_to_deductible' => $policy->coinsuranceContributesToDeductible(),
            ],
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
