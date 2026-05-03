@extends('layouts.dashboard')

@section('title', 'Authorization Detail')
@section('page-title', 'Authorization Detail')

@section('content')
    <div class="max-w-5xl mx-auto">
        <div class="mb-4 flex items-center justify-between">
            <a href="{{ route('authorization-codes.index', request()->only('status')) }}" class="text-xs text-blue-600 hover:underline">
                ← Back to {{ request('status') === 'rejected' ? 'Rejected items' : 'Authorization Codes' }}
            </a>
            @if($authorization->status === 'rejected')
                <span class="inline-flex items-center px-2 py-1 text-[11px] rounded-full bg-red-50 text-red-700 border border-red-200">
                    Rejected transaction – see rejected items below
                </span>
            @endif
        </div>

        <div class="bg-white shadow-sm rounded-lg mb-6">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-slate-900">Authorization {{ $authorization->authorization_reference }}</h1>
                    <p class="text-xs text-slate-500 mt-1">
                        Full breakdown of how we arrived at the client and insurance portions.
                    </p>
                </div>
                <div class="text-xs">
                    <span class="px-2 py-1 rounded-full
                        {{ $authorization->status === 'completed' ? 'bg-green-100 text-green-800' : '' }}
                        {{ $authorization->status === 'pending' ? 'bg-amber-100 text-amber-800' : '' }}
                        {{ $authorization->status === 'failed' ? 'bg-red-100 text-red-800' : 'bg-slate-100 text-slate-700' }}">
                        {{ ucfirst($authorization->status ?? 'N/A') }}
                    </span>
                </div>
            </div>
            <div class="px-6 py-4 space-y-4 text-sm">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <p><span class="font-semibold text-slate-700">Policy Number:</span>
                            <span class="text-slate-800">{{ $authorization->policy?->policy_number ?? '—' }}</span>
                        </p>
                        <p><span class="font-semibold text-slate-700">Invoice #:</span>
                            <span class="text-slate-800">{{ $authorization->external_invoice_number ?? '—' }}</span>
                        </p>
                    </div>
                    <div class="space-y-1">
                        <p><span class="font-semibold text-slate-700">Requested:</span>
                            <span class="text-slate-800">{{ $authorization->requested_at?->format('Y-m-d H:i') ?? '—' }}</span>
                        </p>
                        <p><span class="font-semibold text-slate-700">Completed:</span>
                            <span class="text-slate-800">{{ $authorization->completed_at?->format('Y-m-d H:i') ?? '—' }}</span>
                        </p>
                        <p><span class="font-semibold text-slate-700">Insurer:</span>
                            <span class="text-slate-800">{{ $authorization->insuranceCompany?->name ?? '—' }}</span>
                        </p>
                    </div>
                </div>

                <div class="border-t border-slate-100 pt-3 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <p><span class="font-semibold text-slate-700">Approved amount (invoice total):</span>
                            <span class="text-slate-900">UGX {{ number_format($authorization->total_amount ?? 0, 2) }}</span>
                        </p>
                        <p><span class="font-semibold text-slate-700">Client total (this visit):</span>
                            <span class="text-slate-900">UGX {{ number_format($authorization->client_total ?? 0, 2) }}</span>
                        </p>
                        <p><span class="font-semibold text-slate-700">Insurance total (this visit):</span>
                            <span class="text-slate-900">UGX {{ number_format($authorization->insurance_total ?? 0, 2) }}</span>
                        </p>
                    </div>
                    <div class="space-y-1">
                        <p><span class="font-semibold text-slate-700">Deductible (this visit):</span>
                            <span class="text-slate-800">UGX {{ number_format($breakdown['deductible'] ?? 0, 2) }}</span>
                        </p>
                        <p><span class="font-semibold text-slate-700">Co‑pay (this visit):</span>
                            <span class="text-slate-800">UGX {{ number_format($breakdown['copay'] ?? 0, 2) }}</span>
                        </p>
                        <p><span class="font-semibold text-slate-700">Co‑insurance (this visit):</span>
                            <span class="text-slate-800">UGX {{ number_format($breakdown['coinsurance'] ?? 0, 2) }}</span>
                        </p>
                    </div>
                </div>

                <div class="border-t border-slate-100 pt-3 space-y-3">
                    @php
                        $approved = $authorization->total_amount ?? 0;
                        $dedPart = $breakdown['deductible'] ?? 0;
                        $copayPart = $breakdown['copay'] ?? 0;
                        $coinsPart = $breakdown['coinsurance'] ?? 0;
                        $remainingAfterCopay = $approved - $copayPart;
                        $remainingAfterCoins = $remainingAfterCopay - $coinsPart;
                        $remainingAfterDed   = $remainingAfterCoins - $dedPart;
                        /** OI = pool after co-pay and co-insurance, before applying this visit's deductible slice */
                        $outstandingInvoice = $remainingAfterCoins;
                        /** OD = deductible allocated from OI this visit (same as Step 3), not annual policy balance */
                        $odThisVisit = (float) $dedPart;
                        $v = $outstandingInvoice - $odThisVisit;
                    @endphp

                    <div class="border border-slate-100 rounded-md p-3 bg-slate-50 space-y-1">
                        <p class="text-xs font-semibold text-slate-700 uppercase">Allocation of client share from approved amount</p>
                        <p class="text-xs text-slate-600">
                            Approved amount starts at <strong>UGX {{ number_format($approved, 2) }}</strong>.
                        </p>
                        <ul class="text-xs text-slate-600 list-disc ml-4 space-y-0.5">
                            <li>Step 1 – allocate co‑pay:
                                take <strong>UGX {{ number_format($copayPart, 2) }}</strong>,
                                remaining <strong>UGX {{ number_format($remainingAfterCopay, 2) }}</strong>.
                            </li>
                            <li>Step 2 – allocate co‑insurance:
                                take <strong>UGX {{ number_format($coinsPart, 2) }}</strong>,
                                remaining <strong>UGX {{ number_format($remainingAfterCoins, 2) }}</strong>.
                            </li>
                            <li>Step 3 – allocate deductible from what is left:
                                take <strong>UGX {{ number_format($dedPart, 2) }}</strong>,
                                remaining for insurer <strong>UGX {{ number_format($remainingAfterDed, 2) }}</strong>.
                            </li>
                            <li>Step 4 (continuation) – deductible remainder split:
                                outstanding invoice after co‑pay and co‑insurance
                                <strong>OI = UGX {{ number_format($outstandingInvoice, 2) }}</strong>;
                                deductible taken from that pool this visit
                                <strong>OD = UGX {{ number_format($odThisVisit, 2) }}</strong>
                                (same as Step 3). Compute <strong>V = OI − OD</strong> =
                                <strong>UGX {{ number_format($v, 2) }}</strong>.
                                @if($v > 0)
                                    Because <strong>V &gt; 0</strong>, <strong>OD</strong> stays with the client and <strong>V</strong> is the insurer's share of the post‑co‑pay/co‑ins pool—matching Step 3's remainder for insurer.
                                @else
                                    Because <strong>V ≤ 0</strong>, the full <strong>OI</strong> stays client-side for this slice (insurer <strong>0</strong> from that pool), in addition to co‑pay and co‑insurance.
                                @endif
                            </li>
                        </ul>
                        <p class="text-xs text-slate-600 mt-1">
                            So the client's share is
                            <strong>UGX {{ number_format($dedPart + $copayPart + $coinsPart, 2) }}</strong>
                            and the insurer's share is
                            <strong>UGX {{ number_format($approved - ($dedPart + $copayPart + $coinsPart), 2) }}</strong>,
                            which always adds back to the approved invoice total.
                        </p>
                    </div>

                    @php
                        $rejectedItems = $authorization->rejectedItems ?? collect();
                    @endphp

                    @if($rejectedItems->count() > 0)
                        <div class="border border-red-100 rounded-md p-3 bg-red-50/40 space-y-2">
                            <p class="text-xs font-semibold text-red-700 uppercase">Rejected items for this transaction</p>
                            <p class="text-xs text-red-700">
                                These items were marked as not covered and are fully payable by the client.
                            </p>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-xs divide-y divide-red-100">
                                    <thead class="bg-red-50">
                                        <tr>
                                            <th class="px-3 py-2 text-left font-semibold text-red-800">Item</th>
                                            <th class="px-3 py-2 text-left font-semibold text-red-800">Code</th>
                                            <th class="px-3 py-2 text-right font-semibold text-red-800">Amount (UGX)</th>
                                            <th class="px-3 py-2 text-left font-semibold text-red-800">Scope</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-red-100">
                                        @foreach($rejectedItems as $item)
                                            <tr>
                                                <td class="px-3 py-2 text-slate-800">{{ $item->item_name ?? '—' }}</td>
                                                <td class="px-3 py-2 text-slate-600">{{ $item->item_code ?? '—' }}</td>
                                                <td class="px-3 py-2 text-right text-red-700">
                                                    UGX {{ number_format((float) ($item->amount ?? 0), 2) }}
                                                </td>
                                                <td class="px-3 py-2 text-slate-600 text-[11px]">
                                                    {{ $item->reason_scope ?? 'Not covered' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @else
                        <p class="text-xs text-slate-600">
                            This transaction has no rejected/excluded item lines saved.
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

