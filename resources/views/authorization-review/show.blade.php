@extends('layouts.dashboard')

@section('title', 'Review Pre-Authorization')
@section('page-title', 'Review Pre-Authorization')

@section('content')
<div class="space-y-6">
    <div>
        <a href="{{ route('authorization-review.index') }}" class="text-sm text-slate-600 hover:text-slate-900">&larr; Back to authorization review</a>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">{{ session('error') }}</div>
    @endif

    {{-- Status banner --}}
    <div class="rounded-xl border p-4 flex items-center gap-3
        {{ $preAuthorization->status === 'pending' ? 'bg-yellow-50 border-yellow-200' : ($preAuthorization->status === 'approved' ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200') }}">
        @if($preAuthorization->status === 'pending')
            <svg class="h-6 w-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div>
                <p class="font-semibold text-yellow-800">Pending review</p>
                <p class="text-sm text-yellow-700">This authorization requires manual approval before the insurance portion can be settled.</p>
            </div>
        @elseif($preAuthorization->status === 'approved')
            <svg class="h-6 w-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div>
                <p class="font-semibold text-green-800">Approved</p>
                <p class="text-sm text-green-700">This authorization has been approved.
                    @if($preAuthorization->approval_date) On {{ $preAuthorization->approval_date->format('M d, Y') }}.@endif
                </p>
            </div>
        @else
            <svg class="h-6 w-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div>
                <p class="font-semibold text-red-800">Rejected</p>
                <p class="text-sm text-red-700">{{ $preAuthorization->rejection_reason ?: 'This authorization was rejected.' }}</p>
            </div>
        @endif
    </div>

    {{-- Approval workflow: levels & responsible approvers --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-semibold text-slate-700">Approval workflow</h2>
            <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-medium">{{ $totalLevels }} {{ $totalLevels === 1 ? 'level' : 'levels' }}</span>
        </div>
        <div class="grid grid-cols-1 {{ $totalLevels >= 2 ? 'md:grid-cols-' . min($totalLevels, 4) : '' }} gap-3">
            @for($lvl = 1; $lvl <= $totalLevels; $lvl++)
                @php
                    $approval = $preAuthorization->authorizationApprovals->firstWhere('level', $lvl);
                    $isCurrentUser = in_array($lvl, $userApproverLevels);
                    $isApproved = $approval && $approval->action === 'approved';
                    $isRejected = $approval && $approval->action === 'rejected';
                    $isNextLevel = $nextLevel === $lvl;
                @endphp
                <div class="border rounded-lg p-3 {{ $isApproved ? 'bg-green-50 border-green-200' : ($isRejected ? 'bg-red-50 border-red-200' : ($isNextLevel ? 'bg-blue-50 border-blue-300' : 'bg-slate-50 border-slate-200')) }}">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs font-semibold uppercase {{ $isApproved ? 'text-green-700' : ($isRejected ? 'text-red-700' : ($isNextLevel ? 'text-blue-700' : 'text-slate-500')) }}">
                            Level {{ $lvl }}
                            @if($lvl === 1) — First reviewer @elseif($lvl === 2) — Second reviewer @elseif($lvl === $totalLevels && $totalLevels > 1) — Final approver @endif
                        </p>
                        @if($isApproved)
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-green-200 text-green-800">Approved</span>
                        @elseif($isRejected)
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-red-200 text-red-800">Rejected</span>
                        @elseif($isNextLevel)
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-blue-200 text-blue-800">Next</span>
                        @else
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-slate-200 text-slate-500">Pending</span>
                        @endif
                    </div>

                    {{-- Show who acted (if already approved/rejected) --}}
                    @if($approval)
                        <div class="flex items-center space-x-2 mb-2 p-2 rounded bg-white/60">
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full {{ $isApproved ? 'bg-green-200 text-green-700' : 'bg-red-200 text-red-700' }} text-xs font-bold">{{ strtoupper(substr($approval->user->name ?? '?', 0, 1)) }}</span>
                            <div>
                                <p class="text-sm font-medium text-slate-800">{{ $approval->user->name ?? 'Unknown' }}</p>
                                <p class="text-xs text-slate-500">{{ ucfirst($approval->action) }} {{ $approval->acted_at ? $approval->acted_at->format('M d, Y H:i') : '' }}</p>
                                @if($approval->notes)
                                    <p class="text-xs text-slate-400 italic mt-0.5">{{ $approval->notes }}</p>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Show assigned approvers for this level --}}
                    <p class="text-xs text-slate-500 mb-1">Responsible:</p>
                    @if(isset($approversByLevel[$lvl]) && $approversByLevel[$lvl]->count() > 0)
                        <div class="space-y-1">
                            @foreach($approversByLevel[$lvl] as $approver)
                                <div class="flex items-center space-x-2">
                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-200 text-slate-600 text-xs font-bold">{{ strtoupper(substr($approver->user->name ?? '?', 0, 1)) }}</span>
                                    <div>
                                        <p class="text-sm font-medium text-slate-800">
                                            {{ $approver->user->name ?? 'Unknown' }}
                                            @if($approver->user_id === auth()->id())
                                                <span class="text-xs bg-blue-600 text-white px-1.5 py-0.5 rounded ml-1">You</span>
                                            @endif
                                        </p>
                                        <p class="text-xs text-slate-500">{{ $approver->user->email ?? '' }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-xs text-slate-400 italic">No approvers assigned</p>
                    @endif
                </div>
            @endfor
        </div>
    </div>

    {{-- Two-column layout: Left = details, Right = actions --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- LEFT: Invoice & authorization details (2 cols wide) --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Invoice details --}}
            @if($insuranceAuth)
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <h2 class="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2">
                        <svg class="h-5 w-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Invoice details
                    </h2>

                    <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm mb-5">
                        <div>
                            <dt class="text-slate-500">Invoice number</dt>
                            <dd class="font-semibold text-slate-900">{{ $insuranceAuth->external_invoice_number ?? $invoiceNumber ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Kashtre invoice ID</dt>
                            <dd class="font-medium text-slate-900">{{ $insuranceAuth->kashtre_invoice_id ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Invoice total</dt>
                            <dd class="font-semibold text-slate-900">UGX {{ number_format($insuranceAuth->total_amount, 0) }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Requested at</dt>
                            <dd class="font-medium text-slate-900">{{ $insuranceAuth->requested_at ? $insuranceAuth->requested_at->format('M d, Y H:i') : '—' }}</dd>
                        </div>
                    </dl>

                    {{-- Invoice line items --}}
                    @php $items = $insuranceAuth->metadata['items'] ?? []; @endphp
                    @if(count($items) > 0)
                        <h3 class="text-sm font-semibold text-slate-700 mb-2">Line items</h3>
                        <div class="overflow-x-auto rounded-lg border border-slate-200">
                            <table class="min-w-full text-sm">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left font-medium text-slate-600">#</th>
                                        <th class="px-4 py-2 text-left font-medium text-slate-600">Item</th>
                                        <th class="px-4 py-2 text-right font-medium text-slate-600">Qty</th>
                                        <th class="px-4 py-2 text-right font-medium text-slate-600">Price (UGX)</th>
                                        <th class="px-4 py-2 text-right font-medium text-slate-600">Total (UGX)</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($items as $i => $item)
                                        <tr>
                                            <td class="px-4 py-2 text-slate-500">{{ $i + 1 }}</td>
                                            <td class="px-4 py-2 text-slate-900 font-medium">{{ $item['name'] ?? '—' }}</td>
                                            <td class="px-4 py-2 text-right text-slate-700">{{ $item['quantity'] ?? 1 }}</td>
                                            <td class="px-4 py-2 text-right text-slate-700">{{ number_format($item['price'] ?? 0, 0) }}</td>
                                            <td class="px-4 py-2 text-right text-slate-900 font-medium">{{ number_format($item['total_amount'] ?? (($item['price'] ?? 0) * ($item['quantity'] ?? 1)), 0) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-slate-50">
                                    <tr>
                                        <td colspan="4" class="px-4 py-2 text-right font-semibold text-slate-700">Total</td>
                                        <td class="px-4 py-2 text-right font-bold text-slate-900">UGX {{ number_format($insuranceAuth->total_amount, 0) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @endif
                </div>

                {{-- Financial breakdown --}}
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <h2 class="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2">
                        <svg class="h-5 w-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        Financial breakdown
                    </h2>

                    @php
                        $breakdown = $insuranceAuth->breakdown ?? [];
                        $meta = $insuranceAuth->metadata ?? [];
                    @endphp

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
                            <p class="text-xs font-semibold text-blue-600 uppercase">Insurance portion</p>
                            <p class="text-2xl font-bold text-blue-800 mt-1">UGX {{ number_format($insuranceAuth->insurance_total, 0) }}</p>
                            <p class="text-xs text-blue-500 mt-1">This is the amount to approve/reject</p>
                        </div>
                        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 text-center">
                            <p class="text-xs font-semibold text-amber-600 uppercase">Client portion</p>
                            <p class="text-2xl font-bold text-amber-800 mt-1">UGX {{ number_format($insuranceAuth->client_total, 0) }}</p>
                            <p class="text-xs text-amber-500 mt-1">To be collected from the client</p>
                        </div>
                    </div>

                    @if(!empty($breakdown))
                        <h3 class="text-sm font-semibold text-slate-700 mb-2">Client portion breakdown</h3>
                        <table class="w-full text-sm border border-slate-200 rounded-lg overflow-hidden">
                            <tbody class="divide-y divide-slate-100">
                                @if(($breakdown['deductible'] ?? 0) > 0)
                                    <tr>
                                        <td class="px-4 py-2 text-slate-600">Deductible</td>
                                        <td class="px-4 py-2 text-right font-medium text-slate-900">UGX {{ number_format($breakdown['deductible'], 0) }}</td>
                                    </tr>
                                @endif
                                @if(($breakdown['copay'] ?? 0) > 0)
                                    <tr>
                                        <td class="px-4 py-2 text-slate-600">Co-payment</td>
                                        <td class="px-4 py-2 text-right font-medium text-slate-900">UGX {{ number_format($breakdown['copay'], 0) }}</td>
                                    </tr>
                                @endif
                                @if(($breakdown['coinsurance'] ?? 0) > 0)
                                    <tr>
                                        <td class="px-4 py-2 text-slate-600">Coinsurance</td>
                                        <td class="px-4 py-2 text-right font-medium text-slate-900">UGX {{ number_format($breakdown['coinsurance'], 0) }}</td>
                                    </tr>
                                @endif
                                @if(($breakdown['benefit_excess'] ?? 0) > 0)
                                    <tr>
                                        <td class="px-4 py-2 text-slate-600">Benefit excess</td>
                                        <td class="px-4 py-2 text-right font-medium text-slate-900">UGX {{ number_format($breakdown['benefit_excess'], 0) }}</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    @endif

                    @if(!empty($meta['warnings']))
                        <div class="mt-3 bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                            <p class="text-xs font-semibold text-yellow-700 mb-1">Warnings</p>
                            <ul class="text-xs text-yellow-600 list-disc list-inside space-y-0.5">
                                @foreach($meta['warnings'] as $w)
                                    <li>{{ $w }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if(isset($meta['service_category_name']))
                        <p class="text-xs text-slate-500 mt-3">Service category: <span class="font-medium text-slate-700">{{ $meta['service_category_name'] }}</span></p>
                    @endif
                    @if(isset($meta['authorized_under_grace_period']) && $meta['authorized_under_grace_period'])
                        <p class="text-xs text-amber-600 mt-1">Authorized under grace period (ends {{ $meta['grace_period_end'] ?? '—' }})</p>
                    @endif
                </div>
            @endif

            {{-- Pre-authorization details --}}
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h2 class="text-lg font-bold text-slate-900 mb-4">Pre-authorization details</h2>
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div><dt class="text-slate-500">Time (request)</dt><dd class="font-medium text-slate-900">{{ $preAuthorization->request_date ? $preAuthorization->request_date->format('M d, Y H:i') : ($preAuthorization->created_at ? $preAuthorization->created_at->format('M d, Y H:i') : '—') }}</dd></div>
                    <div><dt class="text-slate-500">Authorization number</dt><dd class="font-medium text-slate-900">{{ $preAuthorization->authorization_number ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500">Policy</dt><dd class="font-medium text-slate-900">{{ $preAuthorization->policy->policy_number ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500">Client</dt><dd class="font-medium text-slate-900">{{ $preAuthorization->client->full_name ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500">Service category</dt><dd class="font-medium text-slate-900">{{ $preAuthorization->serviceCategory->name ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500">Requested amount (UGX)</dt><dd class="font-medium text-slate-900">{{ $preAuthorization->requested_amount ? number_format($preAuthorization->requested_amount, 0) : '—' }}</dd></div>
                    @if($preAuthorization->request_description)
                        <div class="md:col-span-2"><dt class="text-slate-500">Description</dt><dd class="text-slate-900">{{ $preAuthorization->request_description }}</dd></div>
                    @endif
                    @if($preAuthorization->medical_justification)
                        <div class="md:col-span-2"><dt class="text-slate-500">Medical justification</dt><dd class="text-slate-900">{{ $preAuthorization->medical_justification }}</dd></div>
                    @endif
                </dl>
            </div>

            {{-- Multi-level approval progress --}}
            @if($totalLevels > 1 && $preAuthorization->authorizationApprovals->isNotEmpty())
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <h3 class="text-lg font-semibold text-slate-900 mb-3">Approval progress</h3>
                    <div class="space-y-3">
                        @for($l = 1; $l <= $totalLevels; $l++)
                            @php $approval = $preAuthorization->authorizationApprovals->firstWhere('level', $l); @endphp
                            <div class="flex items-center space-x-3 p-3 rounded-lg {{ $approval ? ($approval->action === 'approved' ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200') : 'bg-slate-50 border border-slate-200' }}">
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full text-sm font-bold
                                    {{ $approval ? ($approval->action === 'approved' ? 'bg-green-200 text-green-800' : 'bg-red-200 text-red-800') : 'bg-slate-200 text-slate-500' }}">
                                    {{ $l }}
                                </span>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-slate-900">
                                        Level {{ $l }}
                                        @if($l === 1) &mdash; First Reviewer @elseif($l === 2) &mdash; Second Reviewer @else &mdash; Final Approver @endif
                                    </p>
                                    @if($approval)
                                        <p class="text-xs text-slate-600">
                                            {{ ucfirst($approval->action) }} by {{ $approval->user->name ?? 'Unknown' }}
                                            on {{ $approval->acted_at ? $approval->acted_at->format('M d, Y H:i') : '—' }}
                                            @if($approval->notes) &mdash; {{ $approval->notes }} @endif
                                        </p>
                                    @else
                                        <p class="text-xs text-slate-400">Pending</p>
                                    @endif
                                </div>
                            </div>
                        @endfor
                    </div>
                </div>
            @endif

            {{-- Audit log --}}
            @if($auditLogs->isNotEmpty())
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <h3 class="text-lg font-semibold text-slate-900 mb-3">Audit log</h3>
                    <ul class="space-y-2 text-sm">
                        @foreach($auditLogs as $log)
                            <li class="flex flex-wrap gap-x-2 text-slate-600">
                                <span class="font-medium text-slate-900">{{ ($log->processed_at ?? $log->created_at)->format('M d, Y H:i') }}</span>
                                <span>{{ ucfirst($log->decision ?? '—') }}{{ $log->authorization_method ? ' (' . $log->authorization_method . ')' : '' }}</span>
                                @if($log->notes)<span class="text-slate-500">&ndash; {{ Str::limit($log->notes, 80) }}</span>@endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        {{-- RIGHT: Actions sidebar (1 col wide) --}}
        <div class="space-y-6">

            @if($preAuthorization->status === 'pending')
                @php $canAct = $nextLevel && in_array($nextLevel, $userApproverLevels); @endphp

                @if($canAct)
                    {{-- Approve --}}
                    <div class="bg-white rounded-xl shadow-sm border border-green-200 p-6">
                        <h3 class="text-lg font-semibold text-green-800 mb-3 flex items-center gap-2">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Approve{{ $totalLevels > 1 ? ' (Level ' . $nextLevel . ')' : '' }}
                        </h3>
                        <form action="{{ route('authorization-review.approve', $preAuthorization) }}" method="POST" class="space-y-3">
                            @csrf
                            <div>
                                <label for="approved_amount" class="block text-sm font-medium text-slate-700 mb-1">Approved amount (UGX)</label>
                                <input type="number" name="approved_amount" id="approved_amount" step="0.01" min="0" max="{{ $preAuthorization->requested_amount }}"
                                    value="{{ old('approved_amount', $preAuthorization->requested_amount) }}"
                                    placeholder="Leave blank for full amount"
                                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                                <p class="text-xs text-slate-500 mt-1">Max: UGX {{ number_format($preAuthorization->requested_amount, 0) }}</p>
                            </div>
                            <div>
                                <label for="notes" class="block text-sm font-medium text-slate-700 mb-1">Notes</label>
                                <textarea name="notes" id="notes" rows="2" maxlength="1000" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500" placeholder="Optional">{{ old('notes') }}</textarea>
                            </div>
                            <button type="submit" onclick="return confirm('Approve this authorization{{ $totalLevels > 1 ? ' at Level ' . $nextLevel : '' }}?');"
                                    class="w-full px-4 py-2.5 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-medium">
                                Approve{{ $totalLevels > 1 ? ' (Level ' . $nextLevel . ')' : '' }}
                            </button>
                        </form>
                    </div>

                    {{-- Reject --}}
                    <div class="bg-white rounded-xl shadow-sm border border-red-200 p-6">
                        <h3 class="text-lg font-semibold text-red-800 mb-3 flex items-center gap-2">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Reject
                        </h3>
                        <form action="{{ route('authorization-review.reject', $preAuthorization) }}" method="POST" class="space-y-3">
                            @csrf
                            <div>
                                <label for="rejection_reason" class="block text-sm font-medium text-slate-700 mb-1">Reason (required)</label>
                                <textarea name="rejection_reason" id="rejection_reason" rows="3" maxlength="1000" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500" placeholder="Reason for rejection">{{ old('rejection_reason') }}</textarea>
                            </div>
                            <button type="submit" onclick="return confirm('Reject this authorization?');"
                                    class="w-full px-4 py-2.5 bg-red-600 text-white rounded-lg hover:bg-red-700 transition font-medium">
                                Reject
                            </button>
                        </form>
                    </div>
                @else
                    <div class="bg-slate-50 rounded-xl border border-slate-200 p-4 text-sm text-slate-600">
                        @if($nextLevel)
                            Awaiting Level {{ $nextLevel }} approval. You are not an approver for this level.
                        @else
                            All approval levels completed.
                        @endif
                    </div>
                @endif

                {{-- Reprocess --}}
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <h3 class="text-sm font-semibold text-slate-900 mb-2">Re-run rules</h3>
                    <p class="text-xs text-slate-600 mb-3">Re-process through the authorization rules engine.</p>
                    <form action="{{ route('authorization-review.reprocess', $preAuthorization) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" onclick="return confirm('Re-process this pre-authorization?');" class="w-full px-4 py-2 bg-slate-600 text-white text-sm rounded-lg hover:bg-slate-700 transition">Reprocess</button>
                    </form>
                </div>
            @else
                <div class="bg-slate-50 rounded-xl border border-slate-200 p-4 text-sm text-slate-600">
                    This item is no longer pending. Status: <strong>{{ ucfirst($preAuthorization->status) }}</strong>.
                    @if($preAuthorization->approval_date)
                        Approved on {{ $preAuthorization->approval_date->format('M d, Y') }}.
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
