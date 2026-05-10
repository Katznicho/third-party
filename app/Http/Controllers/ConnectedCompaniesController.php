<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BusinessConnection;
use App\Models\ConnectedCompanyServiceExclusion;
use App\Services\InsurerPortalLedgerPresenter;
use App\Services\KashtreApiService;
use Illuminate\Pagination\LengthAwarePaginator;

class ConnectedCompaniesController extends Controller
{
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

        // Fetch items and excluded items for this provider from Kashtre
        $kashtreBusinessId = (int) $connection->connected_business_id;
        $excludedItems = collect();
        $availableItems = collect();
        $localExclusions = collect();

        if ($kashtreBusinessId > 0) {
            // All items for this business from Kashtre
            $allItems = collect($kashtreApi->getItemsForBusiness($kashtreBusinessId));
            // Items specifically excluded for this insurer+business from Kashtre
            $excludedFromKashtre = collect($kashtreApi->getExcludedItemsForProvider($kashtreBusinessId, $insuranceCompany->id));

            \Log::info('ConnectedCompaniesController@show: Kashtre items fetched', [
                'business_connection_id' => $connection->id,
                'kashtre_business_id' => $kashtreBusinessId,
                'all_items_count' => $allItems->count(),
                'excluded_items_count' => $excludedFromKashtre->count(),
            ]);

            // Local (insurer-portal-only) exclusions for this provider
            $localExclusions = ConnectedCompanyServiceExclusion::where('insurance_company_id', $insuranceCompany->id)
                ->where('business_connection_id', $connection->id)
                ->where('is_active', true)
                ->orderBy('service_category')
                ->orderBy('service_code')
                ->get();

            $excludedIds = $excludedFromKashtre->pluck('id')->filter()->unique()->all();

            // Also exclude locally excluded items by matching service_code to item code
            $codesMap = $allItems->pluck('id', 'code'); // code => id
            $localExcludedCodes = $localExclusions->pluck('service_code')->filter()->unique()->all();
            foreach ($localExcludedCodes as $code) {
                if (isset($codesMap[$code])) {
                    $excludedIds[] = $codesMap[$code];
                }
            }

            // Attach item_name for local exclusions so the view can show names instead of raw codes
            $namesMap = $allItems->pluck('name', 'code'); // code => name
            $localExclusions->transform(function (ConnectedCompanyServiceExclusion $ex) use ($namesMap) {
                $code = $ex->service_code;
                $ex->item_name = $code && isset($namesMap[$code]) ? $namesMap[$code] : null;
                return $ex;
            });

            $excludedIds = array_values(array_unique($excludedIds));

            $excludedItems = $allItems->whereIn('id', $excludedIds)->values();
            $availableItems = $allItems->whereNotIn('id', $excludedIds)->values();
        }

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
