<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BusinessConnection;
use App\Models\ConnectedCompanyItemCoverage;
use App\Models\ConnectedCompanyServiceExclusion;
use App\Models\InsuranceCompany;
use App\Services\InsurerPortalLedgerPresenter;
use App\Services\KashtreApiService;
use Illuminate\Pagination\LengthAwarePaginator;
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

        $excludeAllEligibleCount = $availableItems->filter(function ($item) {
            $code = $item['code'] ?? null;

            return $code !== null && $code !== '';
        })->count();

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

        $ledger = [];
        $ledgerError = null;

        if ($kashtreBusinessId > 0) {
            $result = $kashtreApi->getInsurerPortalVendorSummary($kashtreBusinessId, (int) $insuranceCompany->id);
            if ($result['success']) {
                $ledger = $result['data'] ?? [];
            } else {
                $ledgerError = $result['error'] ?? 'We could not load the financial summary from the service provider system.';
            }
        } else {
            $ledgerError = 'This connection is not linked to a provider business id yet.';
        }

        $recent = collect($ledger['recent_transactions'] ?? []);
        $itemStatementRows = InsurerPortalLedgerPresenter::rowsFromHistoryArrays($recent);

        return view('connected-companies.financial', [
            'insuranceCompany' => $insuranceCompany,
            'connection' => $connection,
            'ledger' => $ledger,
            'ledgerError' => $ledgerError,
            'kashtreBusinessId' => $kashtreBusinessId,
            'itemStatementRows' => $itemStatementRows,
        ]);
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
        if (! in_array($statementView, ['items', 'transactions'], true)) {
            $statementView = 'items';
        }

        $history = null;
        $historyError = null;

        if ($kashtreBusinessId > 0) {
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
        }

        return redirect()
            ->route('connected-companies.show', $connectionId)
            ->with('success', 'Local exclusion added for this provider.');
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
