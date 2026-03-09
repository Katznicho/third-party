@extends('layouts.dashboard')

@section('title', 'Review Pre-Authorization')
@section('page-title', 'Review Pre-Authorization')

@section('content')
<div class="space-y-6">
    <div>
        <a href="{{ route('authorization-review.index') }}" class="text-sm text-slate-600 hover:text-slate-900">&larr; Back to manual clearance</a>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <h2 class="text-xl font-bold text-slate-900 mb-4">Pre-Authorization details</h2>
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div><dt class="text-slate-500">Time (request)</dt><dd class="font-medium text-slate-900">{{ $preAuthorization->request_date ? $preAuthorization->request_date->format('M d, Y H:i') : ($preAuthorization->created_at ? $preAuthorization->created_at->format('M d, Y H:i') : '—') }}</dd></div>
            <div><dt class="text-slate-500">Authorization number</dt><dd class="font-medium text-slate-900">{{ $preAuthorization->authorization_number ?? '—' }}</dd></div>
            <div><dt class="text-slate-500">Policy</dt><dd class="font-medium text-slate-900">{{ $preAuthorization->policy->policy_number ?? '—' }}</dd></div>
            <div><dt class="text-slate-500">Client</dt><dd class="font-medium text-slate-900">{{ $preAuthorization->client->full_name ?? '—' }}</dd></div>
            <div><dt class="text-slate-500">Service category</dt><dd class="font-medium text-slate-900">{{ $preAuthorization->serviceCategory->name ?? '—' }}</dd></div>
            <div><dt class="text-slate-500">Requested amount (UGX)</dt><dd class="font-medium text-slate-900">{{ $preAuthorization->requested_amount ? number_format($preAuthorization->requested_amount, 0) : '—' }}</dd></div>
            <div><dt class="text-slate-500">Status</dt><dd><span class="px-2 py-1 text-xs font-medium rounded-full {{ $preAuthorization->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : ($preAuthorization->status === 'approved' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800') }}">{{ ucfirst($preAuthorization->status ?? 'pending') }}</span></dd></div>
            @if($preAuthorization->request_description)
                <div class="md:col-span-2"><dt class="text-slate-500">Description</dt><dd class="text-slate-900">{{ $preAuthorization->request_description }}</dd></div>
            @endif
            @if($preAuthorization->medical_justification)
                <div class="md:col-span-2"><dt class="text-slate-500">Medical justification</dt><dd class="text-slate-900">{{ $preAuthorization->medical_justification }}</dd></div>
            @endif
        </dl>
    </div>

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

    @if($preAuthorization->status === 'pending')
        @php $canAct = $nextLevel && in_array($nextLevel, $userApproverLevels); @endphp

        @if($canAct)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Approve -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <h3 class="text-lg font-semibold text-slate-900 mb-3">
                        Approve{{ $totalLevels > 1 ? ' (Level ' . $nextLevel . ')' : '' }}
                    </h3>
                    <form action="{{ route('authorization-review.approve', $preAuthorization) }}" method="POST" class="space-y-3">
                        @csrf
                        <div>
                            <label for="approved_amount" class="block text-sm font-medium text-slate-700 mb-1">Approved amount (UGX)</label>
                            <input type="number" name="approved_amount" id="approved_amount" step="0.01" min="0" max="{{ $preAuthorization->requested_amount }}"
                                value="{{ old('approved_amount', $preAuthorization->requested_amount) }}"
                                placeholder="Leave blank for full amount"
                                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <p class="text-xs text-slate-500 mt-1">Max: {{ number_format($preAuthorization->requested_amount, 0) }}</p>
                        </div>
                        <div>
                            <label for="notes" class="block text-sm font-medium text-slate-700 mb-1">Notes</label>
                            <textarea name="notes" id="notes" rows="2" maxlength="1000" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Optional">{{ old('notes') }}</textarea>
                        </div>
                        <button type="submit" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                            Approve{{ $totalLevels > 1 ? ' (Level ' . $nextLevel . ')' : '' }}
                        </button>
                    </form>
                </div>
                <!-- Reject -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <h3 class="text-lg font-semibold text-slate-900 mb-3">Reject</h3>
                    <form action="{{ route('authorization-review.reject', $preAuthorization) }}" method="POST" class="space-y-3">
                        @csrf
                        <div>
                            <label for="rejection_reason" class="block text-sm font-medium text-slate-700 mb-1">Reason (required)</label>
                            <textarea name="rejection_reason" id="rejection_reason" rows="3" maxlength="1000" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Reason for rejection">{{ old('rejection_reason') }}</textarea>
                        </div>
                        <button type="submit" class="w-full px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">Reject</button>
                    </form>
                </div>
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

        <!-- Reprocess -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <h3 class="text-lg font-semibold text-slate-900 mb-2">Re-run rules</h3>
            <p class="text-sm text-slate-600 mb-3">Re-process this pre-authorization through the authorization rules engine.</p>
            <form action="{{ route('authorization-review.reprocess', $preAuthorization) }}" method="POST" class="inline">
                @csrf
                <button type="submit" onclick="return confirm('Re-process this pre-authorization?');" class="px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition">Reprocess</button>
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
@endsection
