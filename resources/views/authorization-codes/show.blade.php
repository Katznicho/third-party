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
                        @if((float) ($breakdown['excluded'] ?? 0) > 0)
                            <p><span class="font-semibold text-slate-700">Excluded / not covered (this visit):</span>
                                <span class="text-slate-800">UGX {{ number_format($breakdown['excluded'] ?? 0, 2) }}</span>
                            </p>
                        @endif
                    </div>
                </div>

                @php
                    $metaItems = collect($metadata['items'] ?? [])->filter(fn ($row) => is_array($row))->values();
                    $excludedBreakdownRows = collect($breakdown['excluded_items'] ?? [])->filter(fn ($row) => is_array($row))->values();
                    $priorCoverageLines = collect($priorCoverage['lines'] ?? [])->filter(fn ($row) => is_array($row))->values();
                    $priorInsurers = collect($priorCoverage['prior_insurers'] ?? [])->filter(fn ($row) => is_array($row))->values();
                    $lineRows = $metaItems->map(function (array $row) use ($excludedBreakdownRows, $priorCoverageLines) {
                        $priorCoverageService = app(\App\Services\AuthorizationPriorCoverageService::class);
                        $name = trim((string) ($row['name'] ?? $row['displayName'] ?? ''));
                        if ($name === '') {
                            $name = '—';
                        }
                        $code = isset($row['code']) ? trim((string) $row['code']) : null;
                        $qty = (float) ($row['quantity'] ?? 1);
                        $price = (float) ($row['price'] ?? 0);
                        $total = (float) ($row['total_amount'] ?? ($price * $qty));
                        $kashtreExcluded = ! empty($row['kashtre_excluded']);
                        $partialRow = $excludedBreakdownRows->first(function ($ex) use ($code, $row) {
                            if (($ex['reason_scope'] ?? '') !== 'partial_coverage') {
                                return false;
                            }
                            $exCode = isset($ex['code']) ? trim((string) $ex['code']) : '';
                            if ($code !== '' && $exCode !== '' && strcasecmp($code, $exCode) === 0) {
                                return true;
                            }
                            $exName = trim((string) ($ex['name'] ?? ''));
                            $rowName = trim((string) ($row['name'] ?? $row['displayName'] ?? ''));

                            return $exName !== '' && $rowName !== '' && strcasecmp($rowName, $exName) === 0;
                        });

                        $inBreakdownExcluded = $partialRow === null && $excludedBreakdownRows->contains(function ($ex) use ($code, $total, $row) {
                            if (($ex['reason_scope'] ?? '') === 'partial_coverage') {
                                return false;
                            }
                            $exCode = isset($ex['code']) ? trim((string) $ex['code']) : '';
                            $exAmt = (float) ($ex['amount'] ?? 0);
                            if ($code !== '' && $exCode !== '' && strcasecmp($code, $exCode) === 0) {
                                return abs($exAmt - $total) < 0.05;
                            }
                            $exName = trim((string) ($ex['name'] ?? ''));
                            if ($exName !== '' && strcasecmp(trim((string) ($row['name'] ?? $row['displayName'] ?? '')), $exName) === 0) {
                                return abs($exAmt - $total) < 0.05;
                            }

                            return false;
                        });
                        $isExcluded = $kashtreExcluded || $inBreakdownExcluded;
                        $priorLine = $priorCoverageService->matchPriorLine(
                            $code !== '' && $code !== '—' ? $code : null,
                            $name !== '—' ? $name : null,
                            $priorCoverageLines->all()
                        );

                        return [
                            'name' => $name,
                            'code' => $code ?: '—',
                            'qty' => $qty,
                            'price' => $price,
                            'total' => $total,
                            'excluded' => $isExcluded,
                            'partial' => $partialRow !== null,
                            'coverage_percent' => $partialRow ? (float) ($partialRow['coverage_percent'] ?? 100) : null,
                            'client_portion' => $partialRow ? (float) ($partialRow['amount'] ?? 0) : null,
                            'prior_covered' => $priorLine,
                        ];
                    });
                    $sumLineTotals = (float) $lineRows->sum('total');
                    $sumCoveredOnly = (float) $lineRows->where('excluded', false)->sum('total');
                @endphp

                @if(isset($siblingAuthorizations) && $siblingAuthorizations->isNotEmpty())
                    <div class="rounded-md border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-700">
                        <p class="font-semibold text-slate-800">Other insurers on this invoice</p>
                        <ul class="mt-1 space-y-1">
                            @foreach($siblingAuthorizations as $sibling)
                                <li>
                                    <a href="{{ route('authorization-codes.show', $sibling) }}" class="text-blue-600 hover:underline font-medium">
                                        {{ $sibling->insuranceCompany?->name ?? 'Insurer' }}
                                    </a>
                                    <span class="text-slate-500">· {{ $sibling->authorization_reference }}</span>
                                    @if((float) ($sibling->insurance_total ?? 0) > 0)
                                        <span class="text-slate-600">· insurance UGX {{ number_format((float) $sibling->insurance_total, 2) }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                        @if(empty($priorCoverage['is_follow_up']))
                            <p class="mt-2 text-slate-600">
                                <strong>Already covered</strong> flags appear on the follow-up insurer’s authorization (items partly paid before cascade).
                            </p>
                        @endif
                    </div>
                @endif

                @if(!empty($priorCoverage['is_follow_up']) && $priorInsurers->isNotEmpty())
                    <div class="rounded-md border border-indigo-200 bg-indigo-50/80 px-4 py-3 text-xs text-indigo-900">
                        <p class="font-semibold">Cascade follow-up authorization</p>
                        <p class="mt-1 text-indigo-800">
                            This request was sent after a higher-priority insurer on the same invoice.
                            Lines marked <span class="inline-flex px-1.5 py-0.5 rounded bg-indigo-100 border border-indigo-200 font-medium">Already covered</span>
                            were partly or fully paid by:
                            @foreach($priorInsurers as $pi)
                                <strong>{{ $pi['vendor_name'] ?? 'Prior insurer' }}</strong>@if(!$loop->last), @endif
                            @endforeach
                        </p>
                    </div>
                @endif

                @if($lineRows->isNotEmpty())
                    <div class="border-t border-slate-100 pt-4">
                        <h2 class="text-sm font-semibold text-slate-900">Itemized breakdown</h2>
                        <p class="text-xs text-slate-500 mt-0.5 mb-3">
                            Line items received with this request to <strong>{{ $authorization->insuranceCompany?->name ?? 'this insurer' }}</strong> for follow-up. Excluded lines are client responsibility on this visit.
                        </p>
                        <div class="overflow-x-auto rounded-md border border-slate-200">
                            <table class="min-w-full text-xs divide-y divide-slate-200">
                                <thead class="bg-slate-100">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-semibold text-slate-700">Item</th>
                                        <th class="px-3 py-2 text-left font-semibold text-slate-700">Code</th>
                                        <th class="px-3 py-2 text-right font-semibold text-slate-700">Qty</th>
                                        <th class="px-3 py-2 text-right font-semibold text-slate-700">Unit (UGX)</th>
                                        <th class="px-3 py-2 text-right font-semibold text-slate-700">Line (UGX)</th>
                                        <th class="px-3 py-2 text-left font-semibold text-slate-700">Coverage</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @foreach($lineRows as $line)
                                        @php
                                            $prior = is_array($line['prior_covered'] ?? null) ? $line['prior_covered'] : null;
                                            $priorAmt = $prior ? (float) ($prior['prior_covered_amount'] ?? 0) : 0;
                                        @endphp
                                        <tr class="{{ $line['excluded'] ? 'bg-amber-50/50' : '' }} {{ $priorAmt > 0 ? 'bg-indigo-50/40' : '' }}">
                                            <td class="px-3 py-2 text-slate-800">
                                                <div>{{ $line['name'] }}</div>
                                                @if($priorAmt > 0)
                                                    <div class="mt-1">
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold bg-indigo-100 text-indigo-900 border border-indigo-200">
                                                            Already covered
                                                            @if(!empty($prior['prior_insurer_name']))
                                                                · {{ $prior['prior_insurer_name'] }}
                                                            @endif
                                                            · UGX {{ number_format($priorAmt, 2) }}
                                                            @if(!empty($prior['coverage_percent']) && (float) $prior['coverage_percent'] > 0 && (float) $prior['coverage_percent'] < 100)
                                                                ({{ rtrim(rtrim(number_format((float) $prior['coverage_percent'], 2, '.', ''), '0'), '.') }}%)
                                                            @endif
                                                        </span>
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 text-slate-600 font-mono">{{ $line['code'] }}</td>
                                            <td class="px-3 py-2 text-right text-slate-700">{{ rtrim(rtrim(number_format($line['qty'], 4, '.', ''), '0'), '.') }}</td>
                                            <td class="px-3 py-2 text-right text-slate-700">{{ number_format($line['price'], 2) }}</td>
                                            <td class="px-3 py-2 text-right font-medium text-slate-900">{{ number_format($line['total'], 2) }}</td>
                                            <td class="px-3 py-2">
                                                @if($priorAmt > 0)
                                                    <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-medium bg-indigo-50 text-indigo-900 border border-indigo-200">Prior insurer share</span>
                                                @elseif($line['excluded'])
                                                    <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-medium bg-amber-100 text-amber-900 border border-amber-200">Excluded</span>
                                                @elseif(!empty($line['partial']))
                                                    <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-medium bg-blue-50 text-blue-900 border border-blue-200">
                                                        {{ rtrim(rtrim(number_format((float) ($line['coverage_percent'] ?? 0), 2, '.', ''), '0'), '.') }}% covered
                                                        @if(!empty($line['client_portion']))
                                                            · client UGX {{ number_format((float) $line['client_portion'], 2) }}
                                                        @endif
                                                    </span>
                                                @else
                                                    <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-medium bg-emerald-50 text-emerald-800 border border-emerald-200">In pool</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-slate-50 border-t border-slate-200">
                                    <tr>
                                        <td colspan="4" class="px-3 py-2 text-right font-semibold text-slate-700">Sum (all lines)</td>
                                        <td class="px-3 py-2 text-right font-semibold text-slate-900">UGX {{ number_format($sumLineTotals, 2) }}</td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td colspan="4" class="px-3 py-2 text-right text-slate-600">Sum (in coverage pool)</td>
                                        <td class="px-3 py-2 text-right text-slate-800 font-medium">UGX {{ number_format($sumCoveredOnly, 2) }}</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <p class="text-[10px] text-slate-500 mt-2">
                            Approved amount on this record (UGX {{ number_format((float) ($authorization->total_amount ?? 0), 2) }}) reflects the visit total after exclusions. Use the rejected-items section below for insurer-managed rejections.
                        </p>
                    </div>
                @else
                    <div class="border-t border-slate-100 pt-4">
                        <h2 class="text-sm font-semibold text-slate-900">Itemized breakdown</h2>
                        <p class="text-xs text-slate-500 mt-1">No line-item payload was stored for this authorization (older requests may predate item capture).</p>
                    </div>
                @endif

                <div class="border-t border-slate-100 pt-3 space-y-3">
                    @php
                        $approved = $authorization->total_amount ?? 0;
                        $dedPart = $breakdown['deductible'] ?? 0;
                        $copayPart = $breakdown['copay'] ?? 0;
                        $coinsPart = $breakdown['coinsurance'] ?? 0;
                        $excludedPart = (float) ($breakdown['excluded'] ?? 0);
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
                            <li>Step 4 – KN remainder split (<strong>V = OI − OD</strong>):
                                <ul class="list-disc ml-4 mt-1 space-y-1">
                                    <li>
                                        <strong>OI</strong> (outstanding invoice) = approved pool after Steps 1–2 =
                                        <strong>UGX {{ number_format($outstandingInvoice, 2) }}</strong>.
                                    </li>
                                    <li>
                                        <strong>OD</strong> (deductible from that pool this visit, same as Step 3) =
                                        <strong>UGX {{ number_format($odThisVisit, 2) }}</strong>.
                                    </li>
                                    <li>
                                        <strong>V = OI − OD</strong> =
                                        <strong>UGX {{ number_format($v, 2) }}</strong>
                                        (what is left for the insurer vs the client after the deductible slice).
                                    </li>
                                </ul>
                            </li>
                            @if($excludedPart > 0)
                                <li>Excluded lines (not part of the approved pool above) add <strong>UGX {{ number_format($excludedPart, 2) }}</strong> to the client’s bill.</li>
                            @endif
                        </ul>
                        <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-2 text-xs">
                            <div class="rounded-md border p-2 {{ $v > 0 ? 'border-emerald-200 bg-emerald-50/80 ring-1 ring-emerald-200' : 'border-slate-200 bg-white' }}">
                                <p class="font-semibold text-slate-800">When <strong>V &gt; 0</strong> (positive remainder)</p>
                                <ul class="list-disc ml-4 mt-1 text-slate-600 space-y-0.5">
                                    <li><strong>Client</strong> pays <strong>OD</strong> from this pool (plus co‑pay and co‑insurance from Steps 1–2).</li>
                                    <li><strong>Insurer / vendor</strong> (this authorization) pays <strong>V</strong> — stored as <strong>Insurance total (this visit)</strong> on this record.</li>
                                </ul>
                            </div>
                            <div class="rounded-md border p-2 {{ $v <= 0 ? 'border-amber-200 bg-amber-50/80 ring-1 ring-amber-200' : 'border-slate-200 bg-white' }}">
                                <p class="font-semibold text-slate-800">When <strong>V ≤ 0</strong> (zero or negative remainder)</p>
                                <ul class="list-disc ml-4 mt-1 text-slate-600 space-y-0.5">
                                    <li><strong>Client</strong> pays the full <strong>OI</strong> from this pool (deductible consumes the insurer’s share).</li>
                                    <li><strong>Insurer / vendor</strong> pays <strong>UGX 0.00</strong> from this pool on this authorization (nothing left after <strong>OD</strong>).</li>
                                </ul>
                            </div>
                        </div>
                        @if($v > 0)
                            <p class="text-xs text-emerald-800 mt-2 font-medium">
                                <strong>This authorization:</strong> <strong>V &gt; 0</strong> — insurer / vendor share
                                <strong>UGX {{ number_format($v, 2) }}</strong>;
                                client slice from this step
                                <strong>UGX {{ number_format($odThisVisit, 2) }}</strong> (OD).
                            </p>
                        @else
                            <p class="text-xs text-amber-900 mt-2 font-medium">
                                <strong>This authorization:</strong> <strong>V ≤ 0</strong> — insurer / vendor share from this pool is
                                <strong>UGX 0.00</strong>;
                                client takes
                                <strong>UGX {{ number_format($outstandingInvoice, 2) }}</strong> (full OI) from this step.
                            </p>
                        @endif
                        <p class="text-xs text-slate-600 mt-1">
                            <strong>Final split stored on this authorization:</strong> client
                            <strong>UGX {{ number_format($authorization->client_total ?? 0, 2) }}</strong>,
                            insurer
                            <strong>UGX {{ number_format($authorization->insurance_total ?? 0, 2) }}</strong>.
                            @php
                                $sumParts = (float) ($authorization->client_total ?? 0) + (float) ($authorization->insurance_total ?? 0);
                                $expectedInvoice = (float) ($authorization->total_amount ?? 0) + $excludedPart;
                            @endphp
                            @if(abs($sumParts - $expectedInvoice) < 0.05)
                                Together these match the invoice total for this authorization (approved pool @if($excludedPart > 0)plus excluded lines @endif).
                            @endif
                            The co‑pay, co‑insurance, and deductible lines explain the cost‑sharing rules; they sum to
                            <strong>UGX {{ number_format($dedPart + $copayPart + $coinsPart, 2) }}</strong>
                            for the covered portion only—then excluded amounts (if any) are added to reach the client total above.
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

