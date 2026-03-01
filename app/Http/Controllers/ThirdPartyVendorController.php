<?php

namespace App\Http\Controllers;

use App\Models\BusinessConnection;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ThirdPartyVendorController extends Controller
{
    /**
     * Show a third-party vendor (connected Kashtre business) and their payments.
     * When record-client-portion stores connected_business_id in payment_metadata, payments are filtered by this vendor.
     * Otherwise shows all Kashtre-sourced payments for this insurer so the section still updates.
     */
    public function show(Request $request, $id)
    {
        $insuranceCompanyId = auth()->user()->insurance_company_id;
        if (!$insuranceCompanyId) {
            abort(403, 'No insurance company associated with your account.');
        }

        $connection = BusinessConnection::where('insurance_company_id', $insuranceCompanyId)
            ->where(function ($q) use ($id) {
                $q->where('id', $id)->orWhere('connected_business_id', $id);
            })
            ->first();

        $connectedBusinessId = $connection ? (int) $connection->connected_business_id : (int) $id;
        $vendorName = $connection ? ($connection->connected_business_name ?? "Vendor #{$connectedBusinessId}") : "Vendor #{$id}";

        // Payments for this insurer from Kashtre; filter by this vendor when payment_metadata->connected_business_id is set
        $payments = Payment::with(['client', 'policy'])
            ->where(function ($q) use ($insuranceCompanyId) {
                $q->whereHas('policy', fn ($p) => $p->where('insurance_company_id', $insuranceCompanyId))
                    ->orWhereRaw('JSON_EXTRACT(payment_metadata, "$.insurance_company_id") = ?', [$insuranceCompanyId]);
            })
            ->where(function ($q) use ($connectedBusinessId) {
                $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(payment_metadata, '$.source')) = 'kashtre'")
                    ->where(function ($q2) use ($connectedBusinessId) {
                        $q2->whereRaw('JSON_EXTRACT(payment_metadata, "$.connected_business_id") = ?', [$connectedBusinessId])
                            ->orWhereNull(DB::raw('JSON_EXTRACT(payment_metadata, "$.connected_business_id")'));
                    });
            })
            ->latest()
            ->paginate(20);

        return view('third-party-vendors.show', [
            'connection' => $connection,
            'vendorName' => $vendorName,
            'connectedBusinessId' => $connectedBusinessId,
            'payments' => $payments,
        ]);
    }
}
