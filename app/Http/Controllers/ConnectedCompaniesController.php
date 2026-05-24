<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BusinessConnection;
use App\Models\ConnectedCompanyItemCoverage;
use App\Models\ConnectedCompanyServiceExclusion;
use App\Models\InsuranceCompany;
use App\Services\InsurerPortalLedgerPresenter;
use App\Services\KashtreApiService;
use App\Models\Payment;
use App\Payments\YoAPI;
use App\Services\ProviderPaymentCompletionService;
use App\Services\ProviderPaymentServiceChargeService;
use App\Support\PaymentReference;
use App\Support\YoPaymentsErrorFormatter;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConnectedCompaniesController extends Controller
{
    /**
     * Kashtre items plus merged exclusions (Kashtre + local by code) for a connection.
     *
     * @return array{
     *   allItems: \Illuminate\Support\Collection,
     *   localExclusions: \Illuminate\Support\Collection,
     *   excludedItems: \Illuminate\Support\Collection,
     *   availableItems: \Illuminate\Support\Collection,
     * }
     */
    protected function buildProviderItemCollections(
        BusinessConnection $connection,
        InsuranceCompany $insuranceCompany,
        KashtreApiService $kashtreApi
    ): array {
        $kashtreBusinessId = (int) $connection->connected_business_id;
        $allItems = collect();
        $localExclusions = collect();
        $excludedItems = collect();
        $availableItems = collect();

        if ($kashtreBusinessId <= 0) {
            return compact('allItems', 'localExclusions', 'excludedItems', 'availableItems');
        }

        $allItems = collect($kashtreApi->getItemsForBusiness($kashtreBusinessId));
        $excludedFromKashtre = collect($kashtreApi->getExcludedItemsForProvider($kashtreBusinessId, $insuranceCompany->id));

        Log::info('ConnectedCompaniesController: Kashtre items fetched', [
            'business_connection_id' => $connection->id,
            'kashtre_business_id' => $kashtreBusinessId,
            'all_items_count' => $allItems->count(),
            'excluded_items_count' => $excludedFromKashtre->count(),
        ]);

        $localExclusions = ConnectedCompanyServiceExclusion::where('insurance_company_id', $insuranceCompany->id)
            ->where('business_connection_id', $connection->id)
            ->where('is_active', true)
            ->orderBy('service_category')
            ->orderBy('service_code')
            ->get();

        $excludedIds = $excludedFromKashtre->pluck('id')->filter()->unique()->all();

        $codesMap = $allItems->pluck('id', 'code');
        $localExcludedCodes = $localExclusions->pluck('service_code')->filter()->unique()->all();
        foreach ($localExcludedCodes as $code) {
            if (isset($codesMap[$code])) {
                $excludedIds[] = $codesMap[$code];
            }
        }

        $namesMap = $allItems->pluck('name', 'code');
        $localExclusions->transform(function (ConnectedCompanyServiceExclusion $ex) use ($namesMap) {
            $code = $ex->service_code;
            $ex->item_name = $code && isset($namesMap[$code]) ? $namesMap[$code] : null;

            return $ex;
        });

        $excludedIds = array_values(array_unique($excludedIds));

        $excludedItems = $allItems->whereIn('id', $excludedIds)->values();
        $availableItems = $allItems->whereNotIn('id', $excludedIds)->values();

        $coverageMap = ConnectedCompanyItemCoverage::activeMapForConnection(
            (int) $insuranceCompany->id,
            (int) $connection->id
        );

        $availableItems = $availableItems->map(function (array $item) use ($coverageMap) {
            $code = trim((string) ($item['code'] ?? ''));
            $percent = 100.0;
            if ($code !== '' && $coverageMap->has(mb_strtolower($code))) {
                $percent = ConnectedCompanyItemCoverage::normalizePercent(
                    (float) $coverageMap->get(mb_strtolower($code))->coverage_percent
                );
            }
            $item['coverage_percent'] = $percent;

            return $item;
        });

        return compact('allItems', 'localExclusions', 'excludedItems', 'availableItems');
    }

    /**
     * Display a listing of service providers.
     */
    public function index()
    {
        $insuranceCompany = auth()->user()->insuranceCompany;
        
        if (!$insuranceCompany) {
            abort(403, 'No insurance company associated with your account.');
        }
        
        // Get connections where this insurance company is the main company
        $connections = $insuranceCompany->connectedCompanies()
            ->latest()
            ->get();

        return view('connected-companies.index', [
            'connections' => $connections,
            'insuranceCompany' => $insuranceCompany,
        ]);
    }

    /**
     * Show a professional view for a specific connected company, including items available for this provider.
     */
    public function show(Request $request, int $connectionId, KashtreApiService $kashtreApi)
    {
        $insuranceCompany = auth()->user()->insuranceCompany;
        
        if (!$insuranceCompany) {
            abort(403, 'No insurance company associated with your account.');
        }

        /** @var BusinessConnection|null $connection */
        $connection = $insuranceCompany->connectedCompanies()
            ->with('connectedBusiness')
            ->findOrFail($connectionId);

        $itemContext = $this->buildProviderItemCollections($connection, $insuranceCompany, $kashtreApi);
        $localExclusions = $itemContext['localExclusions'];
        $excludedItems = $itemContext['excludedItems'];
        $availableItems = $itemContext['availableItems'];

        $exclusionPickerItems = $availableItems
            ->filter(function ($item) {
                $code = trim((string) ($item['code'] ?? ''));

                return $code !== '';
            })
            ->sortBy(fn ($item) => mb_strtolower((string) ($item['name'] ?? $item['code'] ?? '')))
            ->values();

        $excludeAllEligibleCount = $exclusionPickerItems->count();

        $localItemExclusionCount = $localExclusions->filter(function ($ex) {
            $c = $ex->service_code;

            return $c !== null && $c !== '';
        })->count();

        // Search filter (by name or code)
        $search = trim((string) $request->input('q', ''));
        if ($search !== '') {
            $searchLower = mb_strtolower($search);
            $availableItems = $availableItems->filter(function ($item) use ($searchLower) {
                $name = mb_strtolower((string) ($item['name'] ?? ''));
                $code = mb_strtolower((string) ($item['code'] ?? ''));

                return str_contains($name, $searchLower) || str_contains($code, $searchLower);
            })->values();
        }

        // Simple pagination over available items
        $perPage = (int) $request->input('per_page', 25);
        if ($perPage <= 0) {
            $perPage = 25;
        }
        $currentPage = LengthAwarePaginator::resolveCurrentPage() ?: 1;
        $total = $availableItems->count();
        $itemsForPage = $availableItems->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $paginatedItems = new LengthAwarePaginator(
            $itemsForPage,
            $total,
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        // Fetch service categories and category exclusions
        $serviceCategories = \App\Models\ServiceCategory::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $excludedCategories = ConnectedCompanyServiceExclusion::where('insurance_company_id', $insuranceCompany->id)
            ->where('business_connection_id', $connection->id)
            ->where('is_active', true)
            ->whereNotNull('service_category')
            ->pluck('service_category')
            ->unique();

        $availableCategories = $serviceCategories->pluck('slug')
            ->diff($excludedCategories)
            ->map(function($slug) use ($serviceCategories) {
                return optional($serviceCategories->firstWhere('slug', $slug))->name;
            })
            ->filter()
            ->values();

        return view('connected-companies.show', [
            'insuranceCompany' => $insuranceCompany,
            'connection' => $connection,
            'excludedItems' => $excludedItems,
            'localExclusions' => $localExclusions,
            'items' => $paginatedItems,
            'serviceCategories' => $serviceCategories,
            'excludedCategories' => $excludedCategories,
            'availableCategories' => $availableCategories,
            'excludeAllEligibleCount' => $excludeAllEligibleCount,
            'localItemExclusionCount' => $localItemExclusionCount,
            'exclusionPickerItems' => $exclusionPickerItems,
        ]);
    }

    /**
     * Financial summary for a connected provider — mirrors Kashtre third-party vendor detail (Items + Transactions; statement by item or transaction).
     */
    public function financial(int $connectionId, KashtreApiService $kashtreApi)
    {
        $insuranceCompany = auth()->user()->insuranceCompany;

        if (!$insuranceCompany) {
            abort(403, 'No insurance company associated with your account.');
        }

        /** @var BusinessConnection|null $connection */
        $connection = $insuranceCompany->connectedCompanies()
            ->with('connectedBusiness')
            ->findOrFail($connectionId);

        $kashtreBusinessId = (int) $connection->connected_business_id;

        $fetched = $this->fetchLedgerSummary($kashtreApi, $kashtreBusinessId, (int) $insuranceCompany->id);
        $ledger = $fetched['data'];
        $ledgerError = $fetched['error'];

        $recent = collect($ledger['recent_transactions'] ?? []);
        $itemStatementRows = InsurerPortalLedgerPresenter::rowsFromHistoryArrays($recent);

        $payContext = $this->buildPayContext($insuranceCompany, $ledger);

        return view('connected-companies.financial', [
            'insuranceCompany' => $insuranceCompany,
            'connection' => $connection,
            'ledger' => $ledger,
            'ledgerError' => $ledgerError,
            'kashtreBusinessId' => $kashtreBusinessId,
            'itemStatementRows' => $itemStatementRows,
            'canPay' => $payContext['can_pay'],
        ]);
    }

    /**
     * Dedicated payment page for settling provider ledger balance.
     */
    public function showPay(int $connectionId, KashtreApiService $kashtreApi)
    {
        [$insuranceCompany, $connection, $kashtreBusinessId] = $this->resolveFinancialConnection($connectionId);

        $ledger = $this->fetchLedgerSummary($kashtreApi, $kashtreBusinessId, (int) $insuranceCompany->id);
        $ledgerError = $ledger['error'] ?? null;
        $ledgerData = $ledger['data'] ?? [];

        if ($ledgerError) {
            return redirect()
                ->route('connected-companies.financial', $connectionId)
                ->with('error', $ledgerError);
        }

        $payContext = $this->buildPayContext($insuranceCompany, $ledgerData);

        if (! $payContext['can_pay']) {
            return redirect()
                ->route('connected-companies.financial', $connectionId)
                ->with('error', $payContext['block_reason'] ?? 'Payments are not available for this provider yet.');
        }

        if ($payContext['payment_methods'] === []) {
            return redirect()
                ->route('connected-companies.financial', $connectionId)
                ->with('error', 'Configure at least one payment method in your insurance company settings before making payments.');
        }

        $initialChargePreview = null;
        if ($payContext['amount_owed'] > 0) {
            $chargePreview = app(ProviderPaymentServiceChargeService::class)->preview(
                $insuranceCompany,
                $kashtreBusinessId,
                (float) $payContext['amount_owed']
            );
            if ($chargePreview['success'] ?? false) {
                $initialChargePreview = $chargePreview['data'];
            }
        }

        return view('connected-companies.pay', array_merge($payContext, [
            'insuranceCompany' => $insuranceCompany,
            'connection' => $connection,
            'ledger' => $ledgerData,
            'kashtreBusinessId' => $kashtreBusinessId,
            'insurerAccountBalance' => (float) ($insuranceCompany->account_balance ?? 0),
            'initialChargePreview' => $initialChargePreview,
        ]));
    }

    public function previewFinancialPayment(
        Request $request,
        int $connectionId,
        KashtreApiService $kashtreApi
    ) {
        [$insuranceCompany, $connection, $kashtreBusinessId] = $this->resolveFinancialConnection($connectionId);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'history_ids' => 'nullable|array',
            'history_ids.*' => 'integer',
        ]);

        $result = $kashtreApi->previewInsurerPortalPayment(
            $kashtreBusinessId,
            (int) $insuranceCompany->id,
            (float) $validated['amount'],
            $validated['history_ids'] ?? []
        );

        if (! ($result['success'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? 'Could not preview payment.',
            ], 422);
        }

        $data = $result['data'] ?? [];

        if (($data['selection_valid'] ?? true) === false) {
            return response()->json([
                'success' => false,
                'message' => $data['selection_message'] ?? 'Check the items you selected and the payment amount.',
                'data' => $data,
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function storePay(
        Request $request,
        int $connectionId,
        KashtreApiService $kashtreApi,
        ProviderPaymentServiceChargeService $chargeService,
        ProviderPaymentCompletionService $completionService
    ) {
        [$insuranceCompany, $connection, $kashtreBusinessId] = $this->resolveFinancialConnection($connectionId);

        $ledger = $this->fetchLedgerSummary(app(KashtreApiService::class), $kashtreBusinessId, (int) $insuranceCompany->id);
        $payContext = $this->buildPayContext($insuranceCompany, $ledger['data'] ?? []);
        $hasOutstanding = ! empty($payContext['outstanding_entries']);

        $allowedMethods = $insuranceCompany->payment_methods ?: array_keys(InsuranceCompany::getPaymentMethodOptions());
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'history_ids' => ($hasOutstanding ? 'required|array|min:1' : 'nullable|array'),
            'history_ids.*' => 'integer',
            'payment_method' => 'required|string|in:'.implode(',', $allowedMethods),
            'payment_phone' => 'required_if:payment_method,mobile_money|nullable|string|max:20',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
            'service_charge' => 'nullable|numeric|min:0',
            'confirm' => 'accepted',
        ], [
            'history_ids.required' => 'Select at least one item to clear before you continue.',
            'history_ids.min' => 'Select at least one item to clear before you continue.',
            'confirm.accepted' => 'Please confirm the payment details before submitting.',
            'payment_phone.required_if' => 'Enter the mobile money phone number to collect payment from.',
        ]);

        $chargePreview = $chargeService->preview(
            $insuranceCompany,
            $kashtreBusinessId,
            (float) $validated['amount']
        );

        if (! ($chargePreview['success'] ?? false)) {
            return redirect()
                ->route('connected-companies.financial.pay', $connectionId)
                ->withInput()
                ->with('error', $chargePreview['message'] ?? 'Could not calculate service charge.');
        }

        $chargeData = $chargePreview['data'];
        $serviceCharge = (float) $chargeData['service_charge'];
        $totalPaid = (float) $chargeData['total'];
        $providerAmount = (float) $validated['amount'];

        if (isset($validated['service_charge']) && abs((float) $validated['service_charge'] - $serviceCharge) > 0.02) {
            return redirect()
                ->route('connected-companies.financial.pay', $connectionId)
                ->withInput()
                ->with('error', 'Service charge changed. Review the breakdown and submit again.');
        }

        $paymentMethodOptions = InsuranceCompany::getPaymentMethodOptions();
        $providerName = $connection->connected_business_name ?? 'Service provider';

        if ($validated['payment_method'] === 'mobile_money') {
            return $this->storePayMobileMoney(
                $request,
                $connectionId,
                $insuranceCompany,
                $connection,
                $kashtreBusinessId,
                $validated,
                $providerAmount,
                $serviceCharge,
                $totalPaid,
                $providerName,
                $completionService
            );
        }

        $result = $kashtreApi->recordInsurerPortalPayment(
            $kashtreBusinessId,
            (int) $insuranceCompany->id,
            $validated
        );

        if (! ($result['success'] ?? false)) {
            return redirect()
                ->route('connected-companies.financial.pay', $connectionId)
                ->withInput()
                ->with('error', $result['error'] ?? 'Payment could not be recorded.');
        }

        $data = $result['data'] ?? [];

        session()->flash('provider_payment_receipt', [
            'reference' => $data['payment']['reference'] ?? '',
            'amount' => (float) ($data['payment']['amount'] ?? $providerAmount),
            'service_charge' => (float) ($data['service_charge'] ?? $serviceCharge),
            'total_paid' => (float) ($data['total_paid'] ?? $totalPaid),
            'payment_method' => $validated['payment_method'],
            'payment_method_label' => $paymentMethodOptions[$validated['payment_method']] ?? $validated['payment_method'],
            'provider_name' => $providerName,
            'insurer_name' => $insuranceCompany->name,
            'paid_at' => now()->toIso8601String(),
            'new_balance' => (float) ($data['financial']['current_balance'] ?? 0),
        ]);

        return redirect()->route('connected-companies.financial.pay.complete', $connectionId);
    }

    /**
     * Mobile money: collect via Yo first; Kashtre ledger updates after confirmation (cron or manual check).
     */
    protected function storePayMobileMoney(
        Request $request,
        int $connectionId,
        InsuranceCompany $insuranceCompany,
        BusinessConnection $connection,
        int $kashtreBusinessId,
        array $validated,
        float $providerAmount,
        float $serviceCharge,
        float $totalPaid,
        string $providerName,
        ProviderPaymentCompletionService $completionService
    ) {
        $phone = $this->normalizeMobileMoneyPhone((string) ($validated['payment_phone'] ?? ''));
        if ($phone === null) {
            return redirect()
                ->route('connected-companies.financial.pay', $connectionId)
                ->withInput()
                ->with('error', 'Please enter a valid mobile money phone number.');
        }

        // Mobile money always uses a system-generated reference (Yo external ref + ledger).
        $paymentReference = PaymentReference::forProviderPayment($connectionId);
        $user = auth()->user();

        $metadata = [
            'connection_id' => $connectionId,
            'kashtre_business_id' => $kashtreBusinessId,
            'insurance_company_id' => (int) $insuranceCompany->id,
            'provider_amount' => $providerAmount,
            'service_charge' => $serviceCharge,
            'total_collected' => $totalPaid,
            'provider_name' => $providerName,
            'insurer_name' => $insuranceCompany->name,
            'generated_reference' => $paymentReference,
            'history_ids' => array_values(array_map('intval', $validated['history_ids'] ?? [])),
            'kashtre_recorded' => false,
        ];

        try {
            DB::beginTransaction();

            if ($credentialsError = YoPaymentsErrorFormatter::credentialsError()) {
                if (! app()->environment('local')) {
                    DB::rollBack();

                    return redirect()
                        ->route('connected-companies.financial.pay', $connectionId)
                        ->withInput()
                        ->with('error', $credentialsError);
                }
            }

            if (app()->environment('local')) {
                $payment = Payment::create([
                    'payment_reference' => $paymentReference,
                    'payment_type' => 'provider_payment',
                    'amount' => $totalPaid,
                    'paid_amount' => $totalPaid,
                    'balance_amount' => 0,
                    'payment_method' => 'mobile_money',
                    'mobile_money_number' => $phone,
                    'transaction_id' => 'LOCAL-PROV-'.uniqid(),
                    'status' => 'completed',
                    'payment_date' => now(),
                    'processed_at' => now(),
                    'payment_notes' => ($validated['notes'] ?? 'Provider payment (mobile money)') . ' [LOCAL AUTO-COMPLETE]',
                    'payment_metadata' => $metadata,
                    'processed_by' => $user?->id,
                ]);

                $ledger = $completionService->recordOnKashtreLedger($payment);
                if (! ($ledger['success'] ?? false)) {
                    DB::rollBack();

                    return redirect()
                        ->route('connected-companies.financial.pay', $connectionId)
                        ->withInput()
                        ->with('error', $ledger['message'] ?? 'Payment collected but ledger update failed.');
                }

                DB::commit();

                session()->flash('provider_payment_receipt', $completionService->buildReceipt(
                    $payment->fresh(),
                    $ledger['data'] ?? []
                ));

                return redirect()->route('connected-companies.financial.pay.complete', $connectionId);
            }

            $yoApi = new YoAPI(
                config('payments.yo_username'),
                config('payments.yo_password')
            );
            $webhookUrl = (string) config('payments.webhook_url', '');
            if ($webhookUrl !== '') {
                $yoApi->set_instant_notification_url($webhookUrl);
            }
            $yoApi->set_external_reference($paymentReference);

            $narrative = 'Provider payment - '.$providerName.' - '.$insuranceCompany->name;
            if (strlen($narrative) > 160) {
                $narrative = substr($narrative, 0, 157).'...';
            }

            Log::info('Initiating Yo provider payment', [
                'connection_id' => $connectionId,
                'phone' => $phone,
                'total_paid' => $totalPaid,
                'provider_amount' => $providerAmount,
                'reference' => $paymentReference,
                'kashtre_business_id' => $kashtreBusinessId,
            ]);

            try {
                $yoResult = $yoApi->ac_deposit_funds($phone, $totalPaid, $narrative);
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error('YoAPI ac_deposit_funds exception (provider payment)', [
                    'connection_id' => $connectionId,
                    'reference' => $paymentReference,
                    'error' => $e->getMessage(),
                ]);

                return redirect()
                    ->route('connected-companies.financial.pay', $connectionId)
                    ->withInput()
                    ->with('error', 'Yo Payments API error: '.$e->getMessage());
            }

            Log::info('YoAPI provider payment response', ['result' => $yoResult]);

            if (! isset($yoResult['Status']) || $yoResult['Status'] !== 'OK' || empty($yoResult['TransactionReference'])) {
                DB::rollBack();

                return redirect()
                    ->route('connected-companies.financial.pay', $connectionId)
                    ->withInput()
                    ->with('error', YoPaymentsErrorFormatter::formatDepositInitiationFailure(is_array($yoResult) ? $yoResult : []));
            }

            $transactionRef = $yoResult['TransactionReference'];

            $payment = Payment::create([
                'payment_reference' => $paymentReference,
                'payment_type' => 'provider_payment',
                'amount' => $totalPaid,
                'paid_amount' => $totalPaid,
                'balance_amount' => 0,
                'payment_method' => 'mobile_money',
                'mobile_money_number' => $phone,
                'transaction_id' => $transactionRef,
                'status' => 'pending',
                'payment_date' => now(),
                'processed_at' => null,
                'payment_notes' => $validated['notes'] ?? 'Provider payment (mobile money)',
                'payment_metadata' => array_merge($metadata, [
                    'yo_transaction_reference' => $transactionRef,
                    'yo_status' => $yoResult['Status'] ?? null,
                ]),
                'processed_by' => $user?->id,
            ]);

            DB::commit();

            return redirect()
                ->route('connected-companies.financial.pay.pending', [$connectionId, $payment->id])
                ->with('success', 'Payment request sent to '.$phone.'. Complete it on the phone. The provider ledger will update once payment is confirmed.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Provider mobile money payment error', [
                'connection_id' => $connectionId,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('connected-companies.financial.pay', $connectionId)
                ->withInput()
                ->with('error', 'An error occurred while initiating payment: '.$e->getMessage());
        }
    }

    protected function normalizeMobileMoneyPhone(string $phone): ?string
    {
        $phone = preg_replace('/\s+/', '', $phone) ?? '';
        if ($phone === '') {
            return null;
        }
        if (str_starts_with($phone, '+')) {
            $phone = substr($phone, 1);
        } elseif (str_starts_with($phone, '0')) {
            $phone = '256'.substr($phone, 1);
        }
        if (strlen($phone) < 9) {
            return null;
        }

        return $phone;
    }

    public function showPayPending(int $connectionId, Payment $payment, ProviderPaymentCompletionService $completionService)
    {
        [$insuranceCompany, $connection] = $this->resolveProviderPaymentAccess($connectionId, $payment);

        if ($payment->status === 'completed') {
            return $this->redirectAfterProviderPaymentComplete($payment, $connectionId, $completionService);
        }

        if ($payment->status === 'failed') {
            return redirect()
                ->route('connected-companies.financial.pay', $connectionId)
                ->with('error', $payment->failure_reason ?? 'This mobile money payment failed.');
        }

        return view('connected-companies.pay-pending', [
            'insuranceCompany' => $insuranceCompany,
            'connection' => $connection,
            'payment' => $payment,
        ]);
    }

    public function checkPayPending(
        int $connectionId,
        Payment $payment,
        ProviderPaymentCompletionService $completionService
    ) {
        [$insuranceCompany, $connection] = $this->resolveProviderPaymentAccess($connectionId, $payment);

        if ($payment->status === 'completed') {
            return $this->redirectAfterProviderPaymentComplete($payment, $connectionId, $completionService);
        }

        if ($payment->status === 'failed') {
            return redirect()
                ->route('connected-companies.financial.pay.pending', [$connectionId, $payment->id])
                ->with('error', $payment->failure_reason ?? 'This payment failed.');
        }

        if ($payment->payment_method !== 'mobile_money' || empty($payment->transaction_id)) {
            return redirect()
                ->route('connected-companies.financial.pay.pending', [$connectionId, $payment->id])
                ->with('error', 'This payment cannot be checked automatically.');
        }

        if ($credentialsError = YoPaymentsErrorFormatter::credentialsError()) {
            return redirect()
                ->route('connected-companies.financial.pay.pending', [$connectionId, $payment->id])
                ->with('error', $credentialsError);
        }

        $yoApi = new YoAPI(
            config('payments.yo_username'),
            config('payments.yo_password')
        );

        try {
            $statusCheck = $yoApi->ac_transaction_check_status($payment->transaction_id);
        } catch (\Throwable $e) {
            Log::error('checkPayPending Yo status exception', [
                'payment_id' => $payment->id,
                'transaction_id' => $payment->transaction_id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('connected-companies.financial.pay.pending', [$connectionId, $payment->id])
                ->with('error', 'Yo Payments status check error: '.$e->getMessage());
        }

        Log::info('checkPayPending Yo status', [
            'payment_id' => $payment->id,
            'response' => $statusCheck,
        ]);

        $transactionStatus = (string) ($statusCheck['TransactionStatus'] ?? '');

        if ($transactionStatus === 'SUCCEEDED') {
            $result = $completionService->completeProviderMobileMoneyPayment($payment, $statusCheck);
            if (! ($result['success'] ?? false)) {
                return redirect()
                    ->route('connected-companies.financial.pay.pending', [$connectionId, $payment->id])
                    ->with('error', $result['message'] ?? 'Payment was confirmed on mobile money but could not be posted to the provider ledger.');
            }

            return $this->redirectAfterProviderPaymentComplete(
                $result['payment'] ?? $payment->fresh(),
                $connectionId,
                $completionService
            );
        }

        if ($transactionStatus === 'FAILED') {
            $failureDetail = YoPaymentsErrorFormatter::formatResponseDetails($statusCheck);
            $payment->update([
                'status' => 'failed',
                'failure_reason' => $failureDetail,
                'payment_metadata' => array_merge($payment->payment_metadata ?? [], [
                    'yo_status' => $transactionStatus,
                    'yo_status_message' => $statusCheck['StatusMessage'] ?? null,
                    'yo_error_message' => $statusCheck['ErrorMessage'] ?? null,
                    'failed_at' => now()->toDateTimeString(),
                ]),
            ]);

            return redirect()
                ->route('connected-companies.financial.pay.pending', [$connectionId, $payment->id])
                ->with('error', 'Payment failed on mobile money. '.$failureDetail);
        }

        if ($transactionStatus === '') {
            return redirect()
                ->route('connected-companies.financial.pay.pending', [$connectionId, $payment->id])
                ->with('error', YoPaymentsErrorFormatter::formatStatusCheckFailure(is_array($statusCheck) ? $statusCheck : []));
        }

        $payment->update([
            'payment_metadata' => array_merge($payment->payment_metadata ?? [], [
                'last_status_check' => now()->toDateTimeString(),
                'yo_status' => $transactionStatus,
                'yo_status_message' => $statusCheck['StatusMessage'] ?? null,
            ]),
        ]);

        return redirect()
            ->route('connected-companies.financial.pay.pending', [$connectionId, $payment->id])
            ->with('info', YoPaymentsErrorFormatter::formatPendingStatus(is_array($statusCheck) ? $statusCheck : []));
    }

    protected function redirectAfterProviderPaymentComplete(
        Payment $payment,
        int $connectionId,
        ProviderPaymentCompletionService $completionService
    ) {
        $meta = is_array($payment->payment_metadata) ? $payment->payment_metadata : [];

        if (empty($meta['kashtre_recorded'])) {
            $ledger = $completionService->syncKashtreLedgerIfMissing($payment);
            if (! ($ledger['success'] ?? false)) {
                return redirect()
                    ->route('connected-companies.financial.pay.pending', [$connectionId, $payment->id])
                    ->with('error', $ledger['message'] ?? 'Payment is complete but the provider ledger was not updated. Use “Check payment status” to retry.');
            }
            $payment = $payment->fresh();
            $meta = is_array($payment->payment_metadata) ? $payment->payment_metadata : [];
        }

        $kashtreData = is_array($meta['kashtre_result'] ?? null) ? $meta['kashtre_result'] : [];

        session()->flash('provider_payment_receipt', $completionService->buildReceipt($payment, $kashtreData));

        return redirect()->route('connected-companies.financial.pay.complete', $connectionId);
    }

    /**
     * @return array{0: InsuranceCompany, 1: BusinessConnection}
     */
    protected function resolveProviderPaymentAccess(int $connectionId, Payment $payment): array
    {
        $insuranceCompany = auth()->user()->insuranceCompany;
        if (! $insuranceCompany) {
            abort(403);
        }

        $connection = $insuranceCompany->connectedCompanies()->findOrFail($connectionId);

        if ($payment->payment_type !== 'provider_payment') {
            abort(404);
        }

        $meta = is_array($payment->payment_metadata) ? $payment->payment_metadata : [];
        if ((int) ($meta['insurance_company_id'] ?? 0) !== (int) $insuranceCompany->id) {
            abort(403);
        }
        if ((int) ($meta['connection_id'] ?? 0) !== (int) $connectionId) {
            abort(404);
        }

        return [$insuranceCompany, $connection];
    }

    public function payComplete(int $connectionId)
    {
        $insuranceCompany = auth()->user()->insuranceCompany;
        if (! $insuranceCompany) {
            abort(403);
        }

        $connection = $insuranceCompany->connectedCompanies()->findOrFail($connectionId);
        $receipt = session('provider_payment_receipt');

        if (! is_array($receipt) || empty($receipt['reference'])) {
            return redirect()->route('connected-companies.financial', $connectionId);
        }

        return view('connected-companies.pay-complete', [
            'insuranceCompany' => $insuranceCompany,
            'connection' => $connection,
            'receipt' => $receipt,
        ]);
    }

    /**
     * @return array{0: InsuranceCompany, 1: BusinessConnection, 2: int}
     */
    protected function resolveFinancialConnection(int $connectionId): array
    {
        $insuranceCompany = auth()->user()->insuranceCompany;
        if (! $insuranceCompany) {
            abort(403, 'No insurance company associated with your account.');
        }

        $connection = $insuranceCompany->connectedCompanies()->findOrFail($connectionId);
        $kashtreBusinessId = (int) $connection->connected_business_id;
        if ($kashtreBusinessId < 1) {
            abort(422, 'This connection is not linked to a provider business id yet.');
        }

        return [$insuranceCompany, $connection, $kashtreBusinessId];
    }

    /**
     * @return array{data: array, error: ?string}
     */
    protected function fetchLedgerSummary(KashtreApiService $kashtreApi, int $kashtreBusinessId, int $insuranceCompanyId): array
    {
        if ($kashtreBusinessId < 1) {
            return [
                'data' => [],
                'error' => 'This connection is not linked to a provider business id yet.',
            ];
        }

        $result = $kashtreApi->getInsurerPortalVendorSummary($kashtreBusinessId, $insuranceCompanyId);

        return [
            'data' => $result['success'] ? ($result['data'] ?? []) : [],
            'error' => $result['success'] ? null : ($result['error'] ?? 'We could not load account details from the service provider.'),
        ];
    }

    /**
     * @return array{
     *     payer: ?array,
     *     financial: ?array,
     *     amount_owed: float,
     *     current_balance: float,
     *     effective_credit: float,
     *     payment_methods: array<string, string>,
     *     can_pay: bool,
     *     block_reason: ?string
     * }
     */
    protected function buildPayContext(InsuranceCompany $insuranceCompany, array $ledger): array
    {
        $paymentMethodOptions = InsuranceCompany::getPaymentMethodOptions();
        $allowedMethods = $insuranceCompany->payment_methods ?: array_keys($paymentMethodOptions);
        $paymentMethods = array_intersect_key($paymentMethodOptions, array_flip($allowedMethods));

        $payer = $ledger['payer'] ?? null;
        $financial = $ledger['financial'] ?? null;
        $bizCredit = $ledger['business']['max_third_party_credit_limit'] ?? null;

        $currentBalance = (float) ($financial['current_balance'] ?? 0);
        $totalOutstanding = (float) ($financial['total_outstanding'] ?? 0);
        $amountOwed = $totalOutstanding > 0
            ? $totalOutstanding
            : ($currentBalance < 0 ? abs($currentBalance) : 0.0);
        $outstandingEntries = $financial['outstanding_entries'] ?? [];

        $effectiveCredit = 0.0;
        if ($payer && $bizCredit !== null) {
            $cl = $payer['credit_limit'] ?? null;
            $effectiveCredit = ($cl !== null && (float) $cl > 0) ? (float) $cl : (float) $bizCredit;
        }

        $canPay = $payer !== null;
        $blockReason = null;
        if (! $canPay) {
            $blockReason = $ledger['message'] ?? 'No payer account exists for this provider yet.';
        }

        return [
            'payer' => $payer,
            'financial' => $financial,
            'amount_owed' => $amountOwed,
            'total_outstanding' => $totalOutstanding,
            'outstanding_entries' => $outstandingEntries,
            'current_balance' => $currentBalance,
            'effective_credit' => $effectiveCredit,
            'payment_methods' => $paymentMethods,
            'can_pay' => $canPay,
            'block_reason' => $blockReason,
        ];
    }

    /**
     * Full balance statement (paginated) — same ledger as Kashtre “balance statement” for this insurer + provider.
     */
    public function financialStatement(Request $request, int $connectionId, KashtreApiService $kashtreApi)
    {
        $insuranceCompany = auth()->user()->insuranceCompany;

        if (!$insuranceCompany) {
            abort(403, 'No insurance company associated with your account.');
        }

        $connection = $insuranceCompany->connectedCompanies()
            ->with('connectedBusiness')
            ->findOrFail($connectionId);

        $kashtreBusinessId = (int) $connection->connected_business_id;
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 50;

        $statementView = $request->query('view', 'items');
        if ($statementView === 'transactions') {
            $statementView = 'invoices';
        }
        if (! in_array($statementView, ['items', 'invoices'], true)) {
            $statementView = 'items';
        }

        $history = null;
        $historyError = null;
        $ledgerInvoices = [];

        if ($kashtreBusinessId > 0) {
            if ($statementView === 'invoices') {
                $summary = $this->fetchLedgerSummary($kashtreApi, $kashtreBusinessId, (int) $insuranceCompany->id);
                if ($summary['error'] === null) {
                    $ledgerInvoices = $summary['data']['invoices'] ?? [];
                } else {
                    $historyError = $summary['error'] ?? 'We could not load invoices from the service provider system.';
                }
            } else {
                $result = $kashtreApi->getInsurerPortalBalanceHistory(
                    $kashtreBusinessId,
                    (int) $insuranceCompany->id,
                    $page,
                    $perPage
                );
                if ($result['success']) {
                    $history = $result['data'] ?? null;
                } else {
                    $historyError = $result['error'] ?? 'We could not load the statement from the service provider system.';
                }
            }
        } else {
            $historyError = 'This connection is not linked to a provider business id yet.';
        }

        $rows = collect($history['rows'] ?? []);
        $itemStatementRows = $statementView === 'items'
            ? InsurerPortalLedgerPresenter::rowsFromHistoryArrays($rows)
            : collect();

        return view('connected-companies.financial-statement', [
            'insuranceCompany' => $insuranceCompany,
            'connection' => $connection,
            'history' => $history,
            'historyError' => $historyError,
            'page' => $page,
            'statementView' => $statementView,
            'itemStatementRows' => $itemStatementRows,
            'ledgerInvoices' => $ledgerInvoices,
        ]);
    }

    /**
     * Save per-item coverage percentages for this provider (default 100% when omitted).
     */
    public function updateItemCoverages(Request $request, int $connectionId)
    {
        $insuranceCompany = auth()->user()->insuranceCompany;

        if (! $insuranceCompany) {
            abort(403, 'No insurance company associated with your account.');
        }

        $connection = $insuranceCompany->connectedCompanies()->findOrFail($connectionId);

        $validated = $request->validate([
            'coverages' => 'nullable|array',
            'coverages.*.service_code' => 'required|string|max:255',
            'coverages.*.coverage_percent' => 'required|numeric|min:0|max:100',
        ]);

        $rows = $validated['coverages'] ?? [];
        $saved = 0;
        $removed = 0;

        foreach ($rows as $row) {
            $code = trim((string) ($row['service_code'] ?? ''));
            if ($code === '') {
                continue;
            }

            $percent = ConnectedCompanyItemCoverage::normalizePercent((float) ($row['coverage_percent'] ?? 100));

            if ($percent >= 100.0) {
                $deleted = ConnectedCompanyItemCoverage::query()
                    ->where('insurance_company_id', $insuranceCompany->id)
                    ->where('business_connection_id', $connection->id)
                    ->where('service_code', $code)
                    ->delete();
                if ($deleted) {
                    $removed++;
                }

                continue;
            }

            ConnectedCompanyItemCoverage::updateOrCreate(
                [
                    'insurance_company_id' => $insuranceCompany->id,
                    'business_connection_id' => $connection->id,
                    'service_code' => $code,
                ],
                [
                    'coverage_percent' => $percent,
                    'is_active' => true,
                ]
            );
            $saved++;
        }

        $message = $saved > 0
            ? "Coverage updated for {$saved} item(s)."
            : 'Coverage settings saved.';
        if ($removed > 0) {
            $message .= " {$removed} item(s) reset to full coverage (100%).";
        }

        $redirect = redirect()->route('connected-companies.show', $connectionId);
        if ($request->filled('return_q')) {
            $redirect = $redirect->withQuery(['q' => $request->input('return_q')]);
        }
        if ($request->filled('return_page')) {
            $redirect = $redirect->withQuery(['page' => $request->input('return_page')]);
        }

        return $redirect->with('success', $message);
    }

    /**
     * Store a local (insurer-only) exclusion for this provider.
     */
    public function storeLocalExclusion(Request $request, int $connectionId)
    {
        $insuranceCompany = auth()->user()->insuranceCompany;
        
        if (!$insuranceCompany) {
            abort(403, 'No insurance company associated with your account.');
        }

        $connection = $insuranceCompany->connectedCompanies()->findOrFail($connectionId);

        $validated = $request->validate([
            'service_codes' => 'required|array|min:1',
            'service_codes.*' => 'required|string|max:255',
            'reason' => 'nullable|string|max:1000',
        ]);

        $count = 0;
        foreach ($validated['service_codes'] as $code) {
            ConnectedCompanyServiceExclusion::updateOrCreate(
                [
                    'insurance_company_id' => $insuranceCompany->id,
                    'business_connection_id' => $connection->id,
                    'service_code' => $code,
                ],
                [
                    'service_category' => null,
                    'reason' => $validated['reason'] ?? null,
                    'is_active' => true,
                ]
            );
            $count++;
        }

        return redirect()
            ->to(route('connected-companies.show', $connectionId) . '#cc-items-excluded')
            ->with('success', "Local exclusions added for {$count} item(s).");
    }

    /**
     * Remove selected item-level local exclusions (uncheck items in the manager UI).
     */
    public function destroyLocalExclusions(Request $request, int $connectionId)
    {
        $insuranceCompany = auth()->user()->insuranceCompany;

        if (! $insuranceCompany) {
            abort(403, 'No insurance company associated with your account.');
        }

        $connection = $insuranceCompany->connectedCompanies()->findOrFail($connectionId);

        $validated = $request->validate([
            'service_codes' => 'required|array|min:1',
            'service_codes.*' => 'required|string|max:255',
        ]);

        $deleted = ConnectedCompanyServiceExclusion::query()
            ->where('insurance_company_id', $insuranceCompany->id)
            ->where('business_connection_id', $connection->id)
            ->whereIn('service_code', $validated['service_codes'])
            ->delete();

        if ($deleted === 0) {
            return redirect()
                ->to(route('connected-companies.show', $connectionId) . '#cc-items-excluded')
                ->with('warning', 'No matching local exclusions were removed.');
        }

        return redirect()
            ->to(route('connected-companies.show', $connectionId) . '#cc-items-excluded')
            ->with('success', "Removed {$deleted} local item exclusion(s).");
    }

    /**
     * Add local exclusions for every item currently available for this provider (not already excluded).
     */
    public function excludeAllLocalItems(Request $request, int $connectionId, KashtreApiService $kashtreApi)
    {
        $insuranceCompany = auth()->user()->insuranceCompany;

        if (! $insuranceCompany) {
            abort(403, 'No insurance company associated with your account.');
        }

        $connection = $insuranceCompany->connectedCompanies()->findOrFail($connectionId);

        $validated = $request->validate([
            'reason' => 'nullable|string|max:1000',
        ]);

        $itemContext = $this->buildProviderItemCollections($connection, $insuranceCompany, $kashtreApi);
        $availableItems = $itemContext['availableItems'];

        $codes = $availableItems
            ->pluck('code')
            ->filter(fn ($c) => $c !== null && $c !== '')
            ->unique()
            ->values();

        if ($codes->isEmpty()) {
            return redirect()
                ->route('connected-companies.show', $connectionId)
                ->with('warning', 'There are no available items with a service code to exclude.');
        }

        $reason = $validated['reason'] ?? null;
        $count = 0;

        foreach ($codes as $code) {
            ConnectedCompanyServiceExclusion::updateOrCreate(
                [
                    'insurance_company_id' => $insuranceCompany->id,
                    'business_connection_id' => $connection->id,
                    'service_code' => $code,
                ],
                [
                    'service_category' => null,
                    'reason' => $reason,
                    'is_active' => true,
                ]
            );
            $count++;
        }

        return redirect()
            ->route('connected-companies.show', $connectionId)
            ->with('success', "Local exclusions added for {$count} item(s).");
    }

    /**
     * Remove all item-level local exclusions for this provider (category exclusions unchanged).
     */
    public function unexcludeAllLocalItems(int $connectionId)
    {
        $insuranceCompany = auth()->user()->insuranceCompany;

        if (! $insuranceCompany) {
            abort(403, 'No insurance company associated with your account.');
        }

        $connection = $insuranceCompany->connectedCompanies()->findOrFail($connectionId);

        $deleted = ConnectedCompanyServiceExclusion::query()
            ->where('insurance_company_id', $insuranceCompany->id)
            ->where('business_connection_id', $connection->id)
            ->whereNotNull('service_code')
            ->where('service_code', '!=', '')
            ->delete();

        if ($deleted === 0) {
            return redirect()
                ->route('connected-companies.show', $connectionId)
                ->with('warning', 'There were no local item exclusions to remove.');
        }

        return redirect()
            ->route('connected-companies.show', $connectionId)
            ->with('success', "Removed {$deleted} local item exclusion(s).");
    }

    /**
     * Store category exclusions for a connected company.
     */
    public function storeCategoryExclusion(Request $request, int $connectionId)
    {
        $insuranceCompany = auth()->user()->insuranceCompany;
        
        if (!$insuranceCompany) {
            abort(403, 'No insurance company associated with your account.');
        }

        $connection = $insuranceCompany->connectedCompanies()->findOrFail($connectionId);

        $validated = $request->validate([
            'service_categories' => 'required|array|min:1',
            'service_categories.*' => 'required|string|max:255',
            'reason' => 'nullable|string|max:1000',
        ]);

        foreach ($validated['service_categories'] as $category) {
            ConnectedCompanyServiceExclusion::updateOrCreate(
                [
                    'insurance_company_id' => $insuranceCompany->id,
                    'business_connection_id' => $connection->id,
                    'service_category' => $category,
                ],
                [
                    'service_code' => null,
                    'reason' => $validated['reason'] ?? null,
                    'is_active' => true,
                ]
            );
        }

        return redirect()
            ->route('connected-companies.show', $connectionId)
            ->with('success', 'Service category exclusion added for this provider.');
    }

    /**
     * Block a connection.
     */
    public function block(Request $request, int $connectionId)
    {
        $insuranceCompany = auth()->user()->insuranceCompany;
        
        if (!$insuranceCompany) {
            abort(403, 'No insurance company associated with your account.');
        }

        $connection = $insuranceCompany->connectedCompanies()->findOrFail($connectionId);

        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
            'status' => 'required|in:blocked,suspended',
        ]);

        $statusLabel = $validated['status'] === 'suspended' ? 'Suspended' : 'Blocked';
        
        $connection->block(
            $validated['reason'],
            auth()->id(),
            $validated['status']
        );

        return redirect()
            ->route('connected-companies.show', $connectionId)
            ->with('success', "{$statusLabel} successfully.");
    }

    /**
     * Reactivate a blocked/suspended connection.
     */
    public function reactivate(Request $request, int $connectionId)
    {
        $insuranceCompany = auth()->user()->insuranceCompany;
        
        if (!$insuranceCompany) {
            abort(403, 'No insurance company associated with your account.');
        }

        $connection = $insuranceCompany->connectedCompanies()->findOrFail($connectionId);

        if (!$connection->isBlocked() && !$connection->isSuspended()) {
            return redirect()
                ->route('connected-companies.show', $connectionId)
                ->with('error', 'Connection is not blocked or suspended.');
        }

        $connection->reactivate();

        return redirect()
            ->route('connected-companies.show', $connectionId)
            ->with('success', 'Connection reactivated successfully.');
    }
}
