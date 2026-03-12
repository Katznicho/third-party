<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Policy;
use App\Models\PolicyBenefit;
use App\Models\PolicyDeductibleLedger;
use App\Models\InsuranceCompany;
use App\Models\ServiceCategory;
use App\Models\PreAuthorization;
use App\Models\AuthorizationAuditLog;
use App\Models\BusinessConnection;
use App\Models\ConnectedCompanyServiceExclusion;
use App\Models\ClientLocalExclusion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class InvoiceAuthorizationController extends Controller
{
    /**
     * Mapping of Kashtre service-category values to third-party slugs.
     * Kashtre stores a simple string; the third-party uses full slugs.
     */
    private const CATEGORY_SLUG_MAP = [
        'dental'     => 'dental',
        'optical'    => 'optical',
        'outpatient' => 'outpatient',
        'inpatient'  => 'inpatient',
        'maternity'  => 'maternity',
        'funeral'    => 'funeral-expenses',
    ];

    public function request(Request $request): JsonResponse
    {
        Log::info('[InsuranceAuth] Request received', [
            'kashtre_invoice_id' => $request->input('kashtre_invoice_id'),
            'invoice_number' => $request->input('invoice_number'),
            'insurance_company_id' => $request->input('insurance_company_id'),
            'policy_number' => $request->input('policy_number'),
            'services_category' => $request->input('services_category'),
            'total_amount' => $request->input('total_amount'),
            'deductible_remaining' => $request->input('deductible_remaining'),
            'items_count' => is_array($request->input('items')) ? count($request->input('items')) : 0,
        ]);

        $validated = $request->validate([
            'kashtre_invoice_id' => 'required|string|max:64',
            'invoice_number' => 'required|string|max:64',
            'insurance_company_id' => 'required|exists:insurance_companies,id',
            'policy_number' => 'required|string|max:64',
            'services_category' => 'nullable|string|max:64',
            'total_amount' => 'required|numeric|min:0',
            'deductible_remaining' => 'nullable|numeric|min:0',
            'copay_used_this_period' => 'nullable|numeric|min:0',
            'items' => 'nullable|array',
            'items.*.name' => 'nullable|string',
            'items.*.quantity' => 'nullable|numeric',
            'items.*.price' => 'nullable|numeric',
            'items.*.total_amount' => 'nullable|numeric',
            'items.*.code' => 'nullable|string|max:255',
            'items.*.kashtre_excluded' => 'nullable|boolean',
            'connected_business_id' => 'nullable|integer',
        ]);

        $insuranceCompanyId = (int) $validated['insurance_company_id'];
        $policyNumber = trim($validated['policy_number']);
        $totalAmount = (float) $validated['total_amount'];
        $deductibleRemaining = isset($validated['deductible_remaining']) ? (float) $validated['deductible_remaining'] : 0;
        $copayUsedThisPeriod = isset($validated['copay_used_this_period']) ? (float) $validated['copay_used_this_period'] : 0;
        $servicesCategory = $validated['services_category'] ?? null;
        $connectedBusinessId = isset($validated['connected_business_id']) ? (int) $validated['connected_business_id'] : null;
        $itemsPayload = $validated['items'] ?? [];

        // ── Look up policy ──

        $policy = Policy::where('insurance_company_id', $insuranceCompanyId)
            ->where('policy_number', $policyNumber)
            ->with('insuranceCompany')
            ->first();

        if (!$policy) {
            Log::warning('[InsuranceAuth] Policy not found', [
                'insurance_company_id' => $insuranceCompanyId,
                'policy_number' => $policyNumber,
            ]);
            return response()->json(['success' => false, 'message' => 'Policy not found.'], 404);
        }

        if (!$policy->isAuthorizable()) {
            $reason = $policy->status === 'pending_payment'
                ? 'Policy is pending payment and the grace period has expired.'
                : "Policy is not active (status: {$policy->status}).";

            Log::warning('[InsuranceAuth] Policy not authorizable', [
                'policy_id' => $policy->id,
                'status' => $policy->status,
                'reason' => $reason,
            ]);

            return response()->json(['success' => false, 'message' => $reason], 422);
        }

        $insuranceCompany = $policy->insuranceCompany;

        $isGracePeriod = $policy->status === 'pending_payment';
        $gracePeriodEnd = $isGracePeriod ? $policy->getGracePeriodEnd() : null;

        if ($isGracePeriod) {
            Log::info('[InsuranceAuth] Authorizing under grace period', [
                'policy_id' => $policy->id,
                'status' => $policy->status,
                'grace_period_end' => $gracePeriodEnd?->toDateString(),
            ]);
        }

        // ── Service category validation ──

        $serviceCategory = null;
        $policyBenefit = null;
        $benefitWarnings = [];

        if ($servicesCategory) {
            $slug = self::CATEGORY_SLUG_MAP[strtolower($servicesCategory)] ?? strtolower($servicesCategory);

            $serviceCategory = ServiceCategory::where('slug', $slug)
                ->orWhere('name', 'like', $servicesCategory)
                ->orWhere('code', strtoupper($servicesCategory))
                ->first();

            if (!$serviceCategory) {
                Log::warning('[InsuranceAuth] Service category not recognised', ['services_category' => $servicesCategory]);
                $benefitWarnings[] = "Service category '{$servicesCategory}' not recognised.";
            } elseif (!$serviceCategory->is_active) {
                Log::warning('[InsuranceAuth] Service category inactive', ['category' => $serviceCategory->name]);
                return response()->json([
                    'success' => false,
                    'message' => "Service category '{$serviceCategory->name}' is currently inactive.",
                ], 422);
            } else {
                $policyBenefit = PolicyBenefit::where('policy_id', $policy->id)
                    ->where('service_category_id', $serviceCategory->id)
                    ->first();

                if (!$policyBenefit) {
                    Log::warning('[InsuranceAuth] Policy does not cover this category', [
                        'policy_id' => $policy->id,
                        'category' => $serviceCategory->name,
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => "This policy does not cover '{$serviceCategory->name}'. The selected service category is not included in the policy benefits.",
                    ], 422);
                }

                if (!$policyBenefit->is_enabled) {
                    return response()->json([
                        'success' => false,
                        'message' => "The '{$serviceCategory->name}' benefit is disabled for this policy.",
                    ], 422);
                }

                if ($policyBenefit->expiry_date && $policyBenefit->expiry_date->isPast()) {
                    return response()->json([
                        'success' => false,
                        'message' => "The '{$serviceCategory->name}' benefit has expired (expired {$policyBenefit->expiry_date->format('M d, Y')}).",
                    ], 422);
                }

                if ($policyBenefit->effective_date && $policyBenefit->effective_date->isFuture()) {
                    return response()->json([
                        'success' => false,
                        'message' => "The '{$serviceCategory->name}' benefit is not yet effective (starts {$policyBenefit->effective_date->format('M d, Y')}).",
                    ], 422);
                }

                Log::info('[InsuranceAuth] Policy benefit found for category', [
                    'category' => $serviceCategory->name,
                    'benefit_amount' => $policyBenefit->benefit_amount,
                    'used_amount' => $policyBenefit->used_amount,
                    'remaining_amount' => $policyBenefit->remaining_amount,
                ]);

                $remainingBenefit = (float) ($policyBenefit->remaining_amount ?? $policyBenefit->benefit_amount);
                if ($remainingBenefit <= 0) {
                    $benefitWarnings[] = "Benefit for '{$serviceCategory->name}' is fully exhausted.";
                } elseif ($totalAmount > $remainingBenefit) {
                    $benefitWarnings[] = "Invoice amount ({$totalAmount}) exceeds remaining '{$serviceCategory->name}' benefit ({$remainingBenefit}).";
                }
            }
        }

        // ── Exclusion checks (provider-level and client-level) ──

        $excludedAmount = 0.0;
        $excludedItemDetails = [];

        // 1) Provider-level local exclusions (ConnectedCompanyServiceExclusion)
        if ($connectedBusinessId) {
            $connection = BusinessConnection::where('insurance_company_id', $insuranceCompanyId)
                ->where('connected_business_id', $connectedBusinessId)
                ->first();

            if ($connection) {
                $serviceExclusions = ConnectedCompanyServiceExclusion::where('insurance_company_id', $insuranceCompanyId)
                    ->where('business_connection_id', $connection->id)
                    ->where('is_active', true)
                    ->get();

                if ($serviceExclusions->isNotEmpty() && !empty($itemsPayload)) {
                    $excludedCodes = $serviceExclusions->pluck('service_code')->filter()->unique()->values()->all();

                    foreach ($itemsPayload as $item) {
                        $code = $item['code'] ?? null;
                        if (!$code || !in_array($code, $excludedCodes, true)) {
                            continue;
                        }

                        $quantity = (float) ($item['quantity'] ?? 1);
                        $price = (float) ($item['price'] ?? 0);
                        $lineTotal = (float) ($item['total_amount'] ?? ($price * $quantity));

                        $excludedAmount += $lineTotal;
                        $excludedItemDetails[] = [
                            'name' => $item['name'] ?? $code,
                            'code' => $code,
                            'amount' => $lineTotal,
                            'reason_scope' => 'provider',
                        ];
                    }
                }
            }
        }

        // 2) Client-level local exclusions (ClientLocalExclusion) – match by item name
        $client = $policy->principalMember;
        if ($client && !empty($itemsPayload)) {
            $clientLocalExclusions = ClientLocalExclusion::where('insurance_company_id', $insuranceCompanyId)
                ->where('client_id', $client->id)
                ->get();

            if ($clientLocalExclusions->isNotEmpty()) {
                // Collect all excluded item names from reasons (split by ';')
                $nameSet = [];
                foreach ($clientLocalExclusions as $cle) {
                    $reason = trim((string) $cle->reason);
                    if ($reason === '') {
                        continue;
                    }
                    $parts = array_map('trim', explode(';', $reason));
                    foreach ($parts as $part) {
                        if ($part !== '') {
                            $nameSet[$part] = true;
                        }
                    }
                }

                if (!empty($nameSet)) {
                    $excludedNames = array_keys($nameSet);

                    foreach ($itemsPayload as $item) {
                        $name = trim((string) ($item['name'] ?? ''));
                        if ($name === '' || !in_array($name, $excludedNames, true)) {
                            continue;
                        }

                        $quantity = (float) ($item['quantity'] ?? 1);
                        $price = (float) ($item['price'] ?? 0);
                        $lineTotal = (float) ($item['total_amount'] ?? ($price * $quantity));

                        $excludedAmount += $lineTotal;
                        $excludedItemDetails[] = [
                            'name' => $name,
                            'code' => $item['code'] ?? null,
                            'amount' => $lineTotal,
                            'reason_scope' => 'client',
                        ];
                    }
                }
            }
        }

        // 3) Kashtre-side third-party exclusions (flagged as kashtre_excluded in payload)
        if (!empty($itemsPayload)) {
            foreach ($itemsPayload as $item) {
                if (empty($item['kashtre_excluded'])) {
                    continue;
                }

                $quantity = (float) ($item['quantity'] ?? 1);
                $price = (float) ($item['price'] ?? 0);
                $lineTotal = (float) ($item['total_amount'] ?? ($price * $quantity));

                $excludedAmount += $lineTotal;
                $excludedItemDetails[] = [
                    'name' => $item['name'] ?? ($item['code'] ?? 'Excluded item'),
                    'code' => $item['code'] ?? null,
                    'amount' => $lineTotal,
                    'reason_scope' => 'kashtre',
                ];
            }
        }

        // Cap excludedAmount at totalAmount to avoid negatives
        $excludedAmount = min($excludedAmount, $totalAmount);

        // ── Financial split calculation (deductible / copay / coinsurance) ──

        // Only the non-excluded part can be covered by insurance
        $approvedAmount = max(0.0, (float) $totalAmount - $excludedAmount);

        // If we have a policy benefit, cap the insurable amount at the remaining benefit
        $benefitCap = null;
        if ($policyBenefit) {
            $remainingBenefit = (float) ($policyBenefit->remaining_amount ?? $policyBenefit->benefit_amount);
            if ($remainingBenefit >= 0) {
                $benefitCap = $remainingBenefit;
            }
        }

        $deductibleAmountPolicy = $policy->has_deductible ? (float) ($policy->deductible_amount ?? 0) : 0;
        $copayAmount = (float) ($policy->copay_amount ?? 0);
        $copayMaxLimit = $policy->copay_max_limit !== null ? (float) $policy->copay_max_limit : null;
        $coinsurancePct = (float) ($policy->coinsurance_percentage ?? 0);

        if ($policy->has_deductible) {
            $effectiveDeductibleBefore = $deductibleRemaining !== null
                ? max(0.0, (float) $deductibleRemaining)
                : max(0.0, $deductibleAmountPolicy);
        } else {
            $effectiveDeductibleBefore = 0.0;
        }

        $copayCapThisVisit = $copayAmount;
        if ($copayMaxLimit !== null && $copayMaxLimit > 0) {
            $copayRoomLeft = max(0, $copayMaxLimit - $copayUsedThisPeriod);
            $copayCapThisVisit = min($copayAmount, $copayRoomLeft);
        }
        $copayRaw = max(0.0, (float) $copayCapThisVisit);

        $coinsuranceRaw = $approvedAmount > 0 && $coinsurancePct > 0
            ? round($approvedAmount * ($coinsurancePct / 100), 2)
            : 0.0;

        $deductibleRaw = 0.0;
        if ($policy->has_deductible && $approvedAmount > 0) {
            $deductibleRaw = min($effectiveDeductibleBefore, $approvedAmount);
        }

        $remainingForClient = $approvedAmount;

        $copayPortion = min($copayRaw, $remainingForClient);
        $remainingForClient -= $copayPortion;

        $coinsurancePortion = min($coinsuranceRaw, $remainingForClient);
        $remainingForClient -= $coinsurancePortion;

        $deductiblePortion = min($deductibleRaw, $remainingForClient);

        $clientTotalCore = round($deductiblePortion + $copayPortion + $coinsurancePortion, 2);
        $insuranceTotal = round(max(0, $approvedAmount - $clientTotalCore), 2);
        $clientTotal = $clientTotalCore;

        // Cap insurance portion at the remaining category benefit
        $benefitExcess = 0;
        if ($benefitCap !== null && $insuranceTotal > $benefitCap) {
            $benefitExcess = round($insuranceTotal - $benefitCap, 2);
            $insuranceTotal = round($benefitCap, 2);
            $clientTotal = round($approvedAmount - $insuranceTotal, 2);
            $benefitWarnings[] = "Insurance portion capped at remaining benefit ({$benefitCap}). Client pays additional {$benefitExcess}.";
        }

        // Add fully excluded items to client total (insurance never pays for these)
        if ($excludedAmount > 0) {
            $clientTotal = round($clientTotal + $excludedAmount, 2);
        }

        $amountThatReducesDeductible = $deductiblePortion + $policy->calculateDeductibleContribution($copayPortion, $coinsurancePortion);
        $deductibleBefore = $effectiveDeductibleBefore;
        $deductibleAfter = max(0, $deductibleBefore - $amountThatReducesDeductible);

        $breakdown = [
            'deductible' => $deductiblePortion,
            'copay' => $copayPortion,
            'coinsurance' => $coinsurancePortion,
            'excluded' => $excludedAmount,
            'benefit_excess' => $benefitExcess,
            'excluded_items' => $excludedItemDetails,
        ];

        Log::info('[InsuranceAuth] Financial split calculated', [
            'approved_amount' => $approvedAmount,
            'client_total' => $clientTotal,
            'insurance_total' => $insuranceTotal,
            'benefit_cap' => $benefitCap,
            'benefit_excess' => $benefitExcess,
            'breakdown' => $breakdown,
        ]);

        // ── Authorization threshold check ──

        $authorizationStatus = $this->determineAuthorizationStatus($insuranceCompany, $insuranceTotal);

        Log::info('[InsuranceAuth] Authorization status determined', [
            'authorization_status' => $authorizationStatus,
            'insurance_total' => $insuranceTotal,
        ]);

        $authorizationReference = 'AUTH-' . strtoupper(Str::random(12));

        $recordStatus = match ($authorizationStatus) {
            'auto_approved' => 'completed',
            'auto_rejected' => 'rejected',
            default => 'pending_review',
        };

        $auth = \App\Models\InsuranceAuthorization::create([
            'insurance_company_id' => $insuranceCompanyId,
            'policy_id' => $policy->id,
            'kashtre_invoice_id' => $validated['kashtre_invoice_id'],
            'external_invoice_number' => $validated['invoice_number'],
            'total_amount' => $approvedAmount,
            'client_total' => $clientTotal,
            'insurance_total' => $insuranceTotal,
            'breakdown' => $breakdown,
            'status' => $recordStatus,
            'confirmation_code' => null,
            'authorization_reference' => $authorizationReference,
            'requested_at' => now(),
            'completed_at' => $authorizationStatus === 'auto_approved' ? now() : null,
            'metadata' => [
                'items' => $validated['items'] ?? [],
                'authorized_under_grace_period' => $isGracePeriod,
                'grace_period_end' => $gracePeriodEnd?->toDateString(),
                'services_category' => $servicesCategory,
                'service_category_id' => $serviceCategory?->id,
                'service_category_name' => $serviceCategory?->name,
                'policy_benefit_id' => $policyBenefit?->id,
                'benefit_amount' => $policyBenefit?->benefit_amount,
                'benefit_remaining_before' => $policyBenefit ? (float) ($policyBenefit->remaining_amount ?? $policyBenefit->benefit_amount) : null,
                'benefit_cap_applied' => $benefitExcess > 0,
                'deductible_remaining_before' => $deductibleBefore,
                'deductible_remaining_after' => $deductibleAfter,
                'amount_that_reduces_deductible' => $amountThatReducesDeductible,
                'copay_contributes_to_deductible' => $policy->copayContributesToDeductible(),
                'coinsurance_contributes_to_deductible' => $policy->coinsuranceContributesToDeductible(),
                'authorization_status' => $authorizationStatus,
                'warnings' => $benefitWarnings,
                'excluded_items' => $excludedItemDetails,
            ],
        ]);

        // Update used/remaining on the policy benefit
        if ($policyBenefit && $authorizationStatus === 'auto_approved') {
            $policyBenefit->used_amount = (float) $policyBenefit->used_amount + $insuranceTotal;
            $policyBenefit->updateRemainingAmount();
            Log::info('[InsuranceAuth] Policy benefit updated', [
                'benefit_id' => $policyBenefit->id,
                'used_amount' => $policyBenefit->used_amount,
                'remaining_amount' => $policyBenefit->remaining_amount,
            ]);
        }

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

        if ($authorizationStatus === 'pending_review') {
            $preAuth = PreAuthorization::create([
                'authorization_number' => 'AUTH-' . now()->format('Ym') . '-' . strtoupper(Str::random(6)),
                'policy_id' => $policy->id,
                'client_id' => $policy->principal_member_id,
                'service_category_id' => $serviceCategory?->id,
                'request_description' => "Invoice {$validated['invoice_number']} from Kashtre — pending manual review",
                'requested_amount' => $insuranceTotal,
                'status' => 'pending',
                'request_date' => now()->toDateString(),
                'authorization_method' => 'automatic',
            ]);

            AuthorizationAuditLog::create([
                'pre_authorization_id' => $preAuth->id,
                'insurance_company_id' => $insuranceCompanyId,
                'decision' => 'pending_review',
                'authorization_method' => 'automatic',
                'requested_amount' => $insuranceTotal,
                'approved_amount' => 0,
                'rejected_amount' => 0,
                'notes' => "Invoice {$validated['invoice_number']} flagged for manual review — insurance portion ({$insuranceTotal}) exceeds threshold" .
                    ($serviceCategory ? " [{$serviceCategory->name}]" : ''),
                'processed_at' => now(),
            ]);

            Log::info('[InsuranceAuth] PreAuthorization created for review queue', [
                'pre_authorization_id' => $preAuth->id,
                'authorization_number' => $preAuth->authorization_number,
                'insurance_total' => $insuranceTotal,
            ]);
        } else {
            AuthorizationAuditLog::create([
                'pre_authorization_id' => null,
                'insurance_company_id' => $insuranceCompanyId,
                'decision' => $authorizationStatus,
                'authorization_method' => 'automatic',
                'requested_amount' => $insuranceTotal,
                'approved_amount' => $authorizationStatus === 'auto_approved' ? $insuranceTotal : 0,
                'rejected_amount' => $authorizationStatus === 'auto_rejected' ? $insuranceTotal : 0,
                'notes' => "Invoice {$validated['invoice_number']} — {$authorizationStatus}" .
                    ($serviceCategory ? " [{$serviceCategory->name}]" : ''),
                'processed_at' => now(),
            ]);
        }

        Log::info('[InsuranceAuth] Authorization saved', [
            'authorization_id' => $auth->id,
            'authorization_reference' => $authorizationReference,
            'authorization_status' => $authorizationStatus,
            'client_total' => $clientTotal,
            'insurance_total' => $insuranceTotal,
        ]);

        $policyOptions = [
            'has_deductible' => (bool) $policy->has_deductible,
            'deductible_amount' => $policy->has_deductible ? (float) ($policy->deductible_amount ?? 0) : null,
            'copay_amount' => $copayAmount > 0 ? (float) $copayAmount : null,
            'copay_max_limit' => $copayMaxLimit,
            'coinsurance_percentage' => $coinsurancePct > 0 ? (float) $coinsurancePct : null,
        ];

        $response = [
            'success' => true,
            'authorization_reference' => $authorizationReference,
            'authorization_status' => $authorizationStatus,
            'client_total' => $clientTotal,
            'insurance_total' => $insuranceTotal,
            'breakdown' => $breakdown,
            'policy_options' => $policyOptions,
            'amount_that_reduces_deductible' => round($amountThatReducesDeductible, 2),
            'copay_contributes_to_deductible' => $policy->copayContributesToDeductible(),
            'coinsurance_contributes_to_deductible' => $policy->coinsuranceContributesToDeductible(),
        ];

        if ($serviceCategory) {
            $response['service_category'] = [
                'name' => $serviceCategory->name,
                'code' => $serviceCategory->code,
                'benefit_amount' => $policyBenefit?->benefit_amount,
                'benefit_used' => $policyBenefit?->used_amount,
                'benefit_remaining' => $policyBenefit?->remaining_amount,
            ];
        }

        if ($isGracePeriod) {
            $benefitWarnings[] = "Authorized under grace period (payment pending). Grace ends {$gracePeriodEnd?->format('M d, Y')}.";
            $response['grace_period'] = [
                'active' => true,
                'ends_at' => $gracePeriodEnd?->toDateString(),
            ];
        }

        if (!empty($benefitWarnings)) {
            $response['warnings'] = $benefitWarnings;
        }

        return response()->json($response);
    }

    private function determineAuthorizationStatus(InsuranceCompany $company, float $insuranceTotal): string
    {
        if (!$company->enable_auto_authorization) {
            return 'pending_review';
        }

        if ($company->auto_reject_min_amount !== null && $insuranceTotal >= (float) $company->auto_reject_min_amount) {
            return 'auto_rejected';
        }

        if ($company->auto_approve_max_amount !== null && $insuranceTotal <= (float) $company->auto_approve_max_amount) {
            return 'auto_approved';
        }

        if ($company->require_manual_review_above_amount
            && $company->manual_review_threshold_amount !== null
            && $insuranceTotal > (float) $company->manual_review_threshold_amount) {
            return 'pending_review';
        }

        return 'pending_review';
    }
}
