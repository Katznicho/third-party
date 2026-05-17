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
use App\Models\ConnectedCompanyItemCoverage;
use App\Models\ConnectedCompanyServiceExclusion;
use App\Models\MedicalQuestionResponse;
use App\Models\RejectedItem;
use App\Services\ConnectedCompanyItemCoverageService;
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
        $deductibleRemainingFromRequest = isset($validated['deductible_remaining']) ? (float) $validated['deductible_remaining'] : 0;
        $copayUsedThisPeriod = isset($validated['copay_used_this_period']) ? (float) $validated['copay_used_this_period'] : 0;
        $servicesCategory = $validated['services_category'] ?? null;
        $connectedBusinessId = isset($validated['connected_business_id']) ? (int) $validated['connected_business_id'] : null;
        $itemsPayload = $validated['items'] ?? [];
        $connection = null;

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
        } elseif ($policy->status === 'pending_payment' && !$isGracePeriod) {
            Log::info('[InsuranceAuth] Grace period expired', [
                'policy_id' => $policy->id,
                'status' => $policy->status,
                'grace_period_end' => $gracePeriodEnd?->toDateString(),
                'now' => now()->toDateString(),
                'stop_credit_after_grace' => $insuranceCompany->stop_credit_after_grace ?? false,
            ]);
        }

        // If grace period has expired and insurer configured a behavior, apply it
        // IMPORTANT: we still create an InsuranceAuthorization record so the insurer can track rejected transactions.
        if (!$isGracePeriod && $policy->status === 'pending_payment' && ($insuranceCompany->stop_credit_after_grace ?? false)) {
            $behavior = $insuranceCompany->stop_credit_after_grace_behavior ?? 'client_pays_full';
            $authorizationReference = 'AUTH-' . strtoupper(Str::random(12));

            Log::warning('[InsuranceAuth] Grace period expired — applying configured behavior', [
                'policy_id' => $policy->id,
                'total_amount' => $totalAmount,
                'behavior' => $behavior,
            ]);

            // If the invoice is not covered, treat all items as excluded for tracking follow-up.
            $excludedItemsForGrace = [];
            if ($behavior === 'client_pays_full' || $behavior === 'reject_invoice') {
                $excludedItemsForGrace = collect($itemsPayload)->map(function ($item) {
                    $name = $item['name'] ?? ($item['code'] ?? '—');
                    $code = $item['code'] ?? null;
                    $amount = (float) ($item['total_amount'] ?? (($item['price'] ?? 0) * ($item['quantity'] ?? 1)));

                    return [
                        'name' => $name,
                        'code' => $code,
                        'amount' => $amount,
                        'reason_scope' => 'grace_expired',
                    ];
                })->values()->all();
            }

            $authorizationStatus = $behavior === 'manual_review' ? 'pending_review' : 'auto_rejected';
            $recordStatus = $authorizationStatus === 'auto_rejected' ? 'rejected' : 'pending_review';

            $clientTotal = $authorizationStatus === 'auto_rejected' ? (float) $totalAmount : 0.0;
            $insuranceTotal = 0.0;

            $breakdown = [
                'deductible' => 0.0,
                'copay' => 0.0,
                'coinsurance' => 0.0,
                'excluded' => $authorizationStatus === 'auto_rejected' ? (float) $totalAmount : 0.0,
                'benefit_excess' => 0.0,
                'excluded_items' => $excludedItemsForGrace,
            ];

            $policyOptions = [
                'has_deductible' => (bool) $policy->has_deductible,
                'deductible_amount' => $policy->has_deductible ? (float) ($policy->deductible_amount ?? 0) : null,
                'copay_amount' => $policy->copay_amount ? (float) $policy->copay_amount : null,
                'copay_max_limit' => $policy->copay_max_limit,
                'coinsurance_percentage' => $policy->coinsurance_percentage ? (float) $policy->coinsurance_percentage : null,
            ];

            $warnings = match ($behavior) {
                'manual_review' => [
                    'Premium grace period has expired. This invoice has been sent for manual review.',
                ],
                'reject_invoice' => [
                    'Premium grace period has expired. This invoice has been rejected and is not covered by insurance.',
                ],
                default => [
                    'Premium grace period has expired. New invoices are not covered on credit; client must pay the full amount.',
                ],
            };

            // Create InsuranceAuthorization record for tracking.
            $auth = \App\Models\InsuranceAuthorization::create([
                'insurance_company_id' => $insuranceCompanyId,
                'policy_id' => $policy->id,
                'kashtre_invoice_id' => $validated['kashtre_invoice_id'],
                'external_invoice_number' => $validated['invoice_number'],
                'total_amount' => (float) $totalAmount,
                'client_total' => (float) $clientTotal,
                'insurance_total' => (float) $insuranceTotal,
                'breakdown' => $breakdown,
                'status' => $recordStatus,
                'confirmation_code' => null,
                'authorization_reference' => $authorizationReference,
                'requested_at' => now(),
                'completed_at' => $authorizationStatus === 'auto_rejected' ? now() : null,
                'metadata' => [
                    'items' => $validated['items'] ?? [],
                    'connected_business_id' => $connectedBusinessId,
                    'authorized_under_grace_period' => true,
                    'grace_period_end' => $gracePeriodEnd?->toDateString(),
                    'excluded_items' => $excludedItemsForGrace,
                ],
            ]);

            // Create rejected item rows when rejected.
            if ($recordStatus === 'rejected' && !empty($excludedItemsForGrace)) {
                $rows = collect($excludedItemsForGrace)->map(function ($item) use ($auth) {
                    return [
                        'insurance_authorization_id' => $auth->id,
                        'item_name' => $item['name'] ?? '—',
                        'item_code' => $item['code'] ?? null,
                        'amount' => (float) ($item['amount'] ?? 0),
                        'reason_scope' => $item['reason_scope'] ?? 'grace_expired',
                    ];
                })->values()->all();

                RejectedItem::insert($rows);
            }

            return response()->json([
                'success' => true,
                'authorization_reference' => $authorizationReference,
                'authorization_status' => $authorizationStatus,
                'client_total' => (float) $clientTotal,
                'insurance_total' => (float) $insuranceTotal,
                'breakdown' => $breakdown,
                'policy_options' => $policyOptions,
                'warnings' => $warnings,
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
                // Check if this is an open enrollment policy (generic policy)
                $isOpenEnrollmentPolicy = strtoupper($policyNumber) === 'GENERIC-BPXX4Q9C' || 
                                         strpos(strtoupper($policyNumber), 'GENERIC-') === 0;
                
                if ($isOpenEnrollmentPolicy) {
                    // For open enrollment clients, skip PolicyBenefit check and use open enrollment settings
                    Log::info('[InsuranceAuth] Open enrollment client - using open enrollment settings', [
                        'policy_number' => $policyNumber,
                        'service_category' => $serviceCategory->name,
                    ]);
                    
                    // Check if service category is allowed for open enrollment
                    $allowedCategories = $insuranceCompany->open_enrollment_service_categories ?? [];
                    if (is_string($allowedCategories)) {
                        $allowedCategories = json_decode($allowedCategories, true) ?? [];
                    }
                    
                    if (!empty($allowedCategories) && !in_array($serviceCategory->name, $allowedCategories)) {
                        Log::warning('[InsuranceAuth] Service category not allowed for open enrollment', [
                            'policy_number' => $policyNumber,
                            'category' => $serviceCategory->name,
                            'allowed_categories' => $allowedCategories,
                        ]);
                        return response()->json([
                            'success' => false,
                            'message' => "Service category '{$serviceCategory->name}' is not available for open enrollment.",
                        ], 422);
                    }
                    
                    // Check max invoice amount for open enrollment
                    $maxInvoiceAmount = (float) ($insuranceCompany->open_enrollment_max_invoice_amount ?? 0);
                    if ($maxInvoiceAmount > 0 && $totalAmount > $maxInvoiceAmount) {
                        Log::warning('[InsuranceAuth] Open enrollment invoice exceeds max amount', [
                            'policy_number' => $policyNumber,
                            'total_amount' => $totalAmount,
                            'max_invoice_amount' => $maxInvoiceAmount,
                        ]);
                        return response()->json([
                            'success' => false,
                            'message' => "Invoice amount ({$totalAmount}) exceeds the maximum allowed for open enrollment ({$maxInvoiceAmount}).",
                        ], 422);
                    }
                    
                    Log::info('[InsuranceAuth] Open enrollment authorization checks passed', [
                        'policy_number' => $policyNumber,
                        'service_category' => $serviceCategory->name,
                        'total_amount' => $totalAmount,
                        'max_invoice_amount' => $maxInvoiceAmount,
                    ]);
                } else {
                    // Regular policy benefit check
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

                    // Check if benefit is enabled and not expired
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
        }

        // ── Exclusion checks (provider-level, client-level via medical questions, and Kashtre-side) ──

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

        // 2) Client-level exclusions derived from medical questions (exclusion keywords)
        $client = $policy->principalMember;
        if ($client && !empty($itemsPayload)) {
            $triggeredResponses = MedicalQuestionResponse::with('question')
                ->where('client_id', $client->id)
                ->where('triggers_exclusion', true)
                ->get();

            $keywordSet = [];
            foreach ($triggeredResponses as $responseModel) {
                $question = $responseModel->question;
                if (!$question || !$question->has_exclusion_list) {
                    continue;
                }
                // Ensure this question belongs to the same insurer
                if ((int) $question->insurance_company_id !== $insuranceCompanyId) {
                    continue;
                }
                $keywords = $question->exclusion_keywords ?? [];
                foreach ($keywords as $kw) {
                    $kw = trim((string) $kw);
                    if ($kw !== '') {
                        $keywordSet[strtolower($kw)] = true;
                    }
                }
            }

            if (!empty($keywordSet)) {
                $keywords = array_keys($keywordSet);

                foreach ($itemsPayload as $item) {
                    $name = trim((string) ($item['name'] ?? ''));
                    if ($name === '') {
                        continue;
                    }

                    $nameLower = strtolower($name);
                    $matchesKeyword = false;
                    foreach ($keywords as $kw) {
                        if (stripos($nameLower, $kw) !== false) {
                            $matchesKeyword = true;
                            break;
                        }
                    }

                    if (!$matchesKeyword) {
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
                        'reason_scope' => 'client_medical',
                    ];
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

        // 4) Partial coverage (insurer × provider item %; default 100% when not configured)
        if ($connection) {
            [$partialExcluded, $excludedItemDetails] = app(ConnectedCompanyItemCoverageService::class)
                ->applyPartialCoverageToAuthorization(
                    $itemsPayload,
                    $excludedItemDetails,
                    $insuranceCompanyId,
                    (int) $connection->id
                );
            $excludedAmount += $partialExcluded;
        }

        // Cap excludedAmount at totalAmount to avoid negatives
        $excludedAmount = min($excludedAmount, $totalAmount);

        // ── Financial split calculation (deductible / copay / coinsurance) ──

        // Only the non-excluded part can be covered by insurance
        $approvedAmount = max(0.0, (float) $totalAmount - $excludedAmount);

        // Policy benefit remaining (`policy_benefits.remaining_amount`) may still inform warnings below,
        // but we do not cap insurer payment at that figure — splits follow co-pay / co-insurance / deductible / V only.

        $deductibleAmountPolicy = $policy->has_deductible ? (float) ($policy->deductible_amount ?? 0) : 0;

        // Remaining deductible for splits: read-only from ledger rows that were written only after
        // successful client-portion payments (RecordClientPortionService). Authorization never writes the ledger.
        // If no such row yet, use Kashtre’s deductible_remaining from the request as a bootstrap fallback.
        $latestLedger = null;
        $deductibleRemainingAuthoritative = $deductibleAmountPolicy;
        if ($policy->has_deductible) {
            $latestLedger = PolicyDeductibleLedger::where('insurance_company_id', $insuranceCompanyId)
                ->where('policy_id', $policy->id)
                ->latest('id')
                ->first();

            if ($latestLedger) {
                $deductibleRemainingAuthoritative = max(0.0, (float) ($latestLedger->deductible_after ?? $deductibleAmountPolicy));
            } else {
                $deductibleRemainingAuthoritative = max(
                    0.0,
                    min($deductibleAmountPolicy, $deductibleRemainingFromRequest > 0 ? $deductibleRemainingFromRequest : $deductibleAmountPolicy)
                );
            }
        } else {
            $deductibleRemainingAuthoritative = 0.0;
        }
        $copayAmount = (float) ($policy->copay_amount ?? 0);
        $copayMaxLimit = $policy->copay_max_limit !== null ? (float) $policy->copay_max_limit : null;
        $coinsurancePct = (float) ($policy->coinsurance_percentage ?? 0);

        if ($policy->has_deductible) {
            $effectiveDeductibleBefore = max(0.0, (float) $deductibleRemainingAuthoritative);
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

        // Edge case guard:
        // When exclusions leave only a very small covered remainder, a fixed co-pay can
        // consume 100% of that remainder and force insurer_total to zero on every invoice.
        // In that case (no deductible and no coinsurance), let insurer carry the remainder.
        if ($approvedAmount > 0
            && $approvedAmount <= $copayRaw
            && $effectiveDeductibleBefore <= 0
            && $coinsuranceRaw <= 0) {
            Log::info('[InsuranceAuth] Copay floor guard applied', [
                'approved_amount' => $approvedAmount,
                'copay_raw_before' => $copayRaw,
            ]);
            $copayRaw = 0.0;
        }

        $remainingForClient = $approvedAmount;

        // Step 1: allocate fixed co-pay (client folder)
        $copayPortion = min($copayRaw, $remainingForClient);
        $remainingForClient -= $copayPortion;

        // Step 2: allocate co-insurance percentage (client folder)
        $coinsurancePortion = min($coinsuranceRaw, $remainingForClient);
        $remainingForClient -= $coinsurancePortion;

        // Co-pay / co-insurance that *count toward the annual deductible* satisfy part of OD first.
        // Otherwise we would charge copay + coinsurance + a full OD slice (double-count vs the deductible bucket).
        $copayCoinsuranceTowardDeductible = $policy->calculateDeductibleContribution($copayPortion, $coinsurancePortion);

        // Step 3: deductible decision rule (second method)
        // OI = outstanding invoice after co-pay & co-insurance
        // OD = outstanding deductible **still to be allocated from OI** (after copay/coinsurance that already count toward OD)
        // V  = OI - OD
        // If V > 0  => client pays OD, insurer pays V
        // If V <= 0 => client pays OI, insurer pays 0
        $oi = max(0.0, (float) $remainingForClient);
        $od = max(0.0, (float) $effectiveDeductibleBefore - $copayCoinsuranceTowardDeductible);
        $v = round($oi - $od, 2);

        if ($v > 0) {
            $deductiblePortion = min($od, $oi); // effectively OD
            $insuranceTotal = $v;
        } else {
            $deductiblePortion = $oi; // client takes the whole outstanding invoice part
            $insuranceTotal = 0.0;
        }

        $clientTotalCore = round($deductiblePortion + $copayPortion + $coinsurancePortion, 2);
        $clientTotal = $clientTotalCore;

        // No "benefit excess" — insurer share is exactly V from the split above (never truncated by category balance).
        $benefitExcess = 0;

        // Add fully excluded items to client total (insurance never pays for these)
        if ($excludedAmount > 0) {
            $clientTotal = round($clientTotal + $excludedAmount, 2);
        }

        // Total annual deductible satisfied this visit (cannot exceed remaining OD before the visit)
        $amountThatReducesDeductible = min(
            (float) $effectiveDeductibleBefore,
            $deductiblePortion + $copayCoinsuranceTowardDeductible
        );
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
            'deductible_remaining_request' => $deductibleRemainingFromRequest,
            'deductible_remaining_authoritative' => $deductibleRemainingAuthoritative,
            'deductible_ledger_id' => $latestLedger?->id,
            'copay_coinsurance_toward_deductible' => $copayCoinsuranceTowardDeductible,
            'deductible_od_for_oi_split' => $od,
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
                'connected_business_id' => $connectedBusinessId,
                'business_connection_id' => $connection?->id,
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

        // Store excluded/rejected line items in a dedicated table for reliable list/detail display.
        if (!empty($excludedItemDetails)) {
            $rows = collect($excludedItemDetails)
                ->filter(fn ($item) => ($item['reason_scope'] ?? '') !== ConnectedCompanyItemCoverage::REASON_SCOPE_PARTIAL)
                ->map(function ($item) use ($auth) {
                return [
                    'insurance_authorization_id' => $auth->id,
                    'item_name' => $item['name'] ?? '—',
                    'item_code' => $item['code'] ?? null,
                    'amount' => (float) ($item['amount'] ?? 0),
                    'reason_scope' => $item['reason_scope'] ?? null,
                ];
            })->values()->all();

            if (!empty($rows)) {
                RejectedItem::insert($rows);
            }
        }

        // NOTE: Do not reduce policy benefits at authorization time.
        // Benefit reduction is deferred until insurer payment is successfully marked as paid.
        //
        // Policy deductible ledger rows are NOT written here. They are created in
        // RecordClientPortionService when Kashtre confirms the client's mobile-money payment
        // (see CheckPaymentStatus → recordClientPortionPayment). Amounts stay in authorization metadata until then.

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
                // authorization_audit_logs.decision is an ENUM and does not include `pending_review`.
                // Store the correct decision so MySQL doesn't truncate and fail the request.
                'decision' => 'flagged_for_review',
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
