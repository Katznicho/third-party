@extends('layouts.dashboard')

@section('title', 'Vendor – ' . $vendorName)
@section('page-title', 'Third-party vendor')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">{{ $vendorName }}</h1>
            <p class="text-slate-600 mt-1">Payments from this vendor (Kashtre). Updates when client-portion payments are recorded.</p>
        </div>
        <a href="{{ route('connected-companies.index') }}" class="px-4 py-2 border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50 transition duration-150">
            ← Service providers
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        @if($payments->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-gradient-to-r from-slate-50 to-slate-100">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">Payment Reference</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">Policy / Client</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-slate-700 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-slate-700 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        @foreach($payments as $payment)
                            <tr class="hover:bg-slate-50 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-slate-900">{{ $payment->payment_reference }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700">
                                    @if($payment->policy && $payment->policy->policy_number)
                                        {{ $payment->policy->policy_number }}
                                    @else
                                        —
                                    @endif
                                    @if($payment->client && $payment->client->full_name)
                                        <span class="text-slate-500"> · {{ $payment->client->full_name }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold text-green-600">
                                    UGX {{ number_format($payment->amount ?? 0, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $payment->status === 'completed' ? 'bg-green-100 text-green-800' : ($payment->status === 'failed' ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800') }}">
                                        {{ ucfirst($payment->status ?? 'N/A') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                    {{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') : ($payment->created_at ? $payment->created_at->format('d M Y') : '—') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <a href="{{ route('payments.show', $payment) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-slate-200">
                {{ $payments->links() }}
            </div>
        @else
            <div class="p-12 text-center text-slate-500">
                <p>No payments from this vendor yet. Payments will appear here when client-portion payments are recorded from Kashtre.</p>
            </div>
        @endif
    </div>
</div>
@endsection
