@extends('layouts.dashboard')

@section('title', 'Authorization Detail')
@section('page-title', 'Authorization Detail')

@section('content')
    <div class="max-w-5xl mx-auto">
        <div class="mb-4">
            <a href="{{ route('authorization-codes.index') }}" class="text-xs text-blue-600 hover:underline">
                ← Back to Authorization Codes
            </a>
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
                        <p><span class="font-semibold text-slate-700">Kashtre Invoice ID:</span>
                            <span class="text-slate-800">{{ $authorization->kashtre_invoice_id ?? '—' }}</span>
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
                    <p>
                        <span class="font-semibold text-slate-700">How client total is built:</span>
                        <span class="text-slate-800">
                            Client total = Deductible ({{ number_format($breakdown['deductible'] ?? 0, 2) }})
                            @if(($breakdown['copay'] ?? 0) > 0)
                                + Co‑pay ({{ number_format($breakdown['copay'] ?? 0, 2) }})
                            @endif
                            @if(($breakdown['coinsurance'] ?? 0) > 0)
                                + Co‑insurance ({{ number_format($breakdown['coinsurance'] ?? 0, 2) }})
                            @endif
                        </span>
                    </p>
                    @php
                        $amountReduces = $metadata['amount_that_reduces_deductible'] ?? null;
                        $dedBefore = $metadata['deductible_remaining_before'] ?? null;
                        $dedAfter = $metadata['deductible_remaining_after'] ?? null;
                        $approved = $authorization->total_amount ?? 0;
                        $dedPart = $breakdown['deductible'] ?? 0;
                        $copayPart = $breakdown['copay'] ?? 0;
                        $coinsPart = $breakdown['coinsurance'] ?? 0;
                        $remainingAfterCopay = $approved - $copayPart;
                        $remainingAfterCoins = $remainingAfterCopay - $coinsPart;
                        $remainingAfterDed   = $remainingAfterCoins - $dedPart;
                        $outstandingInvoice  = $remainingAfterCoins; // A' = approved - co-pay - co-ins
                        $v                   = $dedBefore !== null ? ($outstandingInvoice - $dedBefore) : null;
                    @endphp
                    @if($amountReduces !== null)
                        <div class="space-y-1">
                            <p>
                                <span class="font-semibold text-slate-700">Amount that reduces deductible:</span>
                                <span class="text-blue-700 font-semibold">
                                    UGX {{ number_format($amountReduces, 2) }}
                                </span>
                            </p>
                            <p class="text-xs text-slate-500">
                                This equals the deductible portion for this visit
                                @if(!empty($metadata['copay_contributes_to_deductible']) && ($breakdown['copay'] ?? 0) > 0)
                                    + co‑pay
                                @endif
                                @if(!empty($metadata['coinsurance_contributes_to_deductible']) && ($breakdown['coinsurance'] ?? 0) > 0)
                                    + co‑insurance
                                @endif
                                for all parts configured to contribute to the deductible.
                            </p>
                        </div>
                    @endif

                    @if($dedBefore !== null && $dedAfter !== null)
                        <div class="border border-slate-100 rounded-md p-3 bg-slate-50 space-y-1">
                            <p class="text-xs font-semibold text-slate-700 uppercase">Deductible tracking</p>
                            <p class="text-xs text-slate-600">
                                Started with <strong>UGX {{ number_format($dedBefore, 2) }}</strong>,
                                reduced by <strong>UGX {{ number_format($amountReduces ?? 0, 2) }}</strong>,
                                leaving <strong>UGX {{ number_format($dedAfter, 2) }}</strong> outstanding.
                            </p>
                        </div>
                    @endif

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
                        </ul>
                        <p class="text-xs text-slate-600 mt-1">
                            So the client's share is
                            <strong>UGX {{ number_format($dedPart + $copayPart + $coinsPart, 2) }}</strong>
                            and the insurer's share is
                            <strong>UGX {{ number_format($approved - ($dedPart + $copayPart + $coinsPart), 2) }}</strong>,
                            which always adds back to the approved invoice total.
                        </p>
                    </div>

                    @if($dedBefore !== null && $v !== null)
                        <div class="border border-slate-100 rounded-md p-3 bg-slate-50 space-y-1">
                            <p class="text-xs font-semibold text-slate-700 uppercase">Deductible decision rule (your formula)</p>
                            <p class="text-xs text-slate-600">
                                Step 0 – inputs:
                                outstanding invoice amount after co‑pay and co‑insurance
                                <strong>A' = UGX {{ number_format($outstandingInvoice, 2) }}</strong>,
                                outstanding deductible
                                <strong>D₀ = UGX {{ number_format($dedBefore, 2) }}</strong>.
                            </p>
                            <p class="text-xs text-slate-600">
                                Step 1 – compute <strong>v = A' − D₀</strong> =
                                <strong>UGX {{ number_format($outstandingInvoice, 2) }}</strong> −
                                <strong>UGX {{ number_format($dedBefore, 2) }}</strong>
                                = <strong>UGX {{ number_format($v, 2) }}</strong>.
                            </p>
                            @if($v > 0)
                                <p class="text-xs text-slate-600">
                                    Because v &gt; 0, insurer pays this positive difference:
                                    <strong>Insurer pays UGX {{ number_format($v, 2) }}</strong>,
                                    and the client pays:
                                    <strong>co‑pay + co‑insurance + D₀</strong>,
                                    which matches the client total above.
                                </p>
                            @else
                                <p class="text-xs text-slate-600">
                                    Because v ≤ 0, the outstanding deductible is greater than or equal to the outstanding invoice,
                                    so <strong>insurer pays 0</strong> and the client pays the full approved amount (split into
                                    deductible, co‑pay and co‑insurance as shown above).
                                </p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

