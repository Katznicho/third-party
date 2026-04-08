<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ConnectedCompanyServiceExclusion;
use App\Models\BusinessConnection;
use App\Models\InsuranceAuthorization;
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
     * Financial summary page for a specific connected company (provider).
     */
    public function financial(Request $request, int $connectionId)
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

        $query = InsuranceAuthorization::where('insurance_company_id', $insuranceCompany->id);

        if ($kashtreBusinessId > 0) {
            $query->where('metadata->connected_business_id', $kashtreBusinessId);
        }

        if ($request->filled('invoice_number')) {
            $invoice = $request->get('invoice_number');
            $query->where('external_invoice_number', 'like', '%' . $invoice . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('from')) {
            $query->whereDate('requested_at', '>=', $request->get('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('requested_at', '<=', $request->get('to'));
        }

        $authorizations = $query->orderByDesc('requested_at')->paginate(25)->withQueryString();

        $totalApproved = 0.0;
        $totalGuaranteed = 0.0;
        $totalClientPortion = 0.0;
        $totalExcluded = 0.0;

        foreach ($authorizations as $auth) {
            $breakdown = $auth->breakdown ?? [];
            $totalApproved += (float) ($auth->total_amount ?? 0);
            $totalGuaranteed += (float) ($auth->insurance_total ?? 0);
            $totalClientPortion += (float) ($auth->client_total ?? 0);
            $totalExcluded += (float) ($breakdown['excluded'] ?? 0);
        }

        return view('connected-companies.financial', [
            'insuranceCompany' => $insuranceCompany,
            'connection' => $connection,
            'authorizations' => $authorizations,
            'totalApproved' => $totalApproved,
            'totalGuaranteed' => $totalGuaranteed,
            'totalClientPortion' => $totalClientPortion,
            'totalExcluded' => $totalExcluded,
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
