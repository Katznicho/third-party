<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Business Settings') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if(session('success'))
                        <div class="mb-4 bg-green-50 border-l-4 border-green-400 p-4 rounded">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-green-700">{{ session('success') }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="mb-4 bg-red-50 border-l-4 border-red-400 p-4 rounded">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-red-700">{{ session('error') }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Visit Authorization Duration Settings</h3>
                        <p class="text-sm text-gray-600 mb-6">
                            Configure how long visits should be authorized for each business. Visits older than this period will be moved to archive automatically.
                        </p>

                        @if($businessConnections->count() > 0)
                            <div class="space-y-6">
                                @foreach($businessConnections as $businessConnection)
                                    <div class="bg-gray-50 rounded-lg p-6">
                                        <form action="{{ route('business-settings.update', $businessConnection) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                                <div>
                                                    <h4 class="text-md font-medium text-gray-900 mb-3">
                                                        {{ $businessConnection->connected_business_name }}
                                                        @if($businessConnection->connection_type)
                                                            <span class="ml-2 text-sm text-gray-500">({{ $businessConnection->connection_type }})</span>
                                                        @endif
                                                    </h4>
                                                    <p class="text-sm text-gray-600 mb-4">
                                                        Connection ID: {{ $businessConnection->id }}
                                                    </p>
                                                </div>

                                                <div>
                                                    <label for="visit_authorization_duration_{{ $businessConnection->id }}" class="block text-sm font-medium text-gray-700 mb-2">
                                                        Visit Authorization Duration (days)
                                                    </label>
                                                    <div class="flex items-center space-x-2">
                                                        <input 
                                                            type="number" 
                                                            id="visit_authorization_duration_{{ $businessConnection->id }}"
                                                            name="visit_authorization_duration" 
                                                            value="{{ App\Models\BusinessSetting::getVisitAuthorizationDuration($businessConnection->id) }}"
                                                            min="1" 
                                                            max="365"
                                                            class="block w-24 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                                        >
                                                        <span class="text-sm text-gray-500">days</span>
                                                    </div>
                                                    @error('visit_authorization_duration')
                                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                                    @enderror
                                                    <p class="mt-2 text-xs text-gray-500">
                                                        Current setting: {{ App\Models\BusinessSetting::getVisitAuthorizationDuration($businessConnection->id) }} days
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="mt-4">
                                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-md hover:bg-blue-700 transition duration-150">
                                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7l-7 7-7-4z"/>
                                                    </svg>
                                                    Update Setting
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-4">
                                {{ $businessConnections->links() }}
                            </div>
                        @else
                            <div class="text-center py-12">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No business connections found</h3>
                                <p class="mt-1 text-sm text-gray-500">Please add business connections first.</p>
                        </div>
                        @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
