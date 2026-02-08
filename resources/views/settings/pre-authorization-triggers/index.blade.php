@extends('layouts.dashboard')

@section('title', 'Pre-Authorization Triggers')
@section('page-title', 'Pre-Authorization Triggers')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Pre-Authorization Triggers</h1>
            <p class="text-slate-600 mt-1">Configure automatic triggers for high-cost services, special procedures, and keyword-based events</p>
        </div>
        <button onclick="openCreateModal()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            + Add Trigger
        </button>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    <!-- Triggers Table -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Priority</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Trigger Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Configuration</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Auto-Create</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-200">
                @forelse($triggers as $trigger)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">{{ $trigger->priority }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-slate-900">{{ $trigger->trigger_name }}</div>
                            @if($trigger->description)
                                <div class="text-xs text-slate-500">{{ Str::limit($trigger->description, 50) }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-slate-900">{{ ucwords(str_replace('_', ' ', $trigger->trigger_type)) }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-slate-900">
                                @if($trigger->cost_threshold)
                                    <div>Cost: ≥ {{ number_format($trigger->cost_threshold, 2) }}</div>
                                @endif
                                @if($trigger->serviceCategory)
                                    <div>Category: {{ $trigger->serviceCategory->name }}</div>
                                @endif
                                @if($trigger->keywords && count($trigger->keywords) > 0)
                                    <div>Keywords: {{ implode(', ', array_slice($trigger->keywords, 0, 3)) }}{{ count($trigger->keywords) > 3 ? '...' : '' }}</div>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $trigger->auto_create_preauth ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ $trigger->auto_create_preauth ? 'Yes' : 'No' }}
                            </span>
                            @if($trigger->auto_create_preauth && $trigger->auto_approval_limit)
                                <div class="text-xs text-slate-500 mt-1">Auto-approve: ≤ {{ number_format($trigger->auto_approval_limit, 2) }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $trigger->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ $trigger->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button onclick="openEditModal({{ $trigger->id }})" class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                            <form action="{{ route('settings.pre-authorization-triggers.destroy', $trigger) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this trigger?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-sm text-slate-500">
                            No triggers configured. Click "Add Trigger" to create one.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="flex justify-start">
        <a href="{{ route('settings.index') }}" class="text-blue-600 hover:text-blue-800">← Back to Settings</a>
    </div>
</div>

<!-- Create/Edit Modal -->
<div id="triggerModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white max-h-[90vh] overflow-y-auto">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-slate-900 mb-4" id="modalTitle">Add Pre-Authorization Trigger</h3>
            <form id="triggerForm" method="POST">
                @csrf
                <div id="formMethod" style="display: none;"></div>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Trigger Name *</label>
                        <input type="text" name="trigger_name" id="trigger_name" required
                               class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
                        <textarea name="description" id="description" rows="2"
                                  class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Trigger Type *</label>
                            <select name="trigger_type" id="trigger_type" required onchange="updateTriggerFields()"
                                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="high_cost_service">High Cost Service</option>
                                <option value="special_procedure">Special Procedure</option>
                                <option value="keyword_match">Keyword Match</option>
                                <option value="service_category">Service Category</option>
                                <option value="cost_threshold">Cost Threshold</option>
                                <option value="custom">Custom</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Priority *</label>
                            <input type="number" name="priority" id="priority" min="1" max="1000" value="100" required
                                   class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <p class="text-xs text-slate-500 mt-1">Lower number = higher priority</p>
                        </div>
                    </div>

                    <!-- Service Category -->
                    <div id="serviceCategoryField">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Service Category</label>
                        <select name="service_category_id" id="service_category_id"
                                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Categories</option>
                            @foreach($serviceCategories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Cost Threshold -->
                    <div id="costThresholdField">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Cost Threshold (UGX)</label>
                        <input type="number" name="cost_threshold" id="cost_threshold" step="0.01" min="0"
                               class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="e.g., 100000">
                        <p class="text-xs text-slate-500 mt-1">Trigger when service cost exceeds this amount</p>
                    </div>

                    <!-- Keywords -->
                    <div id="keywordsField">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Keywords (one per line)</label>
                        <textarea name="keywords_text" id="keywords_text" rows="4"
                                  class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                  placeholder="surgery&#10;operation&#10;procedure"></textarea>
                        <p class="text-xs text-slate-500 mt-1">Enter keywords that should trigger pre-authorization (one per line)</p>
                    </div>

                    <!-- Auto-Create Settings -->
                    <div class="border-t border-slate-200 pt-4">
                        <h4 class="text-sm font-semibold text-slate-800 mb-3">Auto-Create Settings</h4>
                        
                        <div class="space-y-3">
                            <label class="flex items-start">
                                <input type="checkbox" name="auto_create_preauth" id="auto_create_preauth" value="1"
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-slate-300 rounded mt-1"
                                       onchange="toggleAutoApprovalFields()">
                                <div class="ml-3">
                                    <span class="block text-sm font-medium text-slate-700">Auto-Create Pre-Authorization</span>
                                    <p class="text-xs text-slate-500 mt-1">Automatically create pre-authorization when trigger matches</p>
                                </div>
                            </label>

                            <div id="autoApprovalFields" class="hidden space-y-2 pl-7">
                                <label class="flex items-start">
                                    <input type="checkbox" name="require_manual_approval" id="require_manual_approval" value="1" checked
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-slate-300 rounded mt-1">
                                    <div class="ml-3">
                                        <span class="block text-sm font-medium text-slate-700">Require Manual Approval</span>
                                        <p class="text-xs text-slate-500 mt-1">Pre-authorization requires manual approval</p>
                                    </div>
                                </label>

                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Auto-Approval Limit (UGX)</label>
                                    <input type="number" name="auto_approval_limit" id="auto_approval_limit" step="0.01" min="0"
                                           class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                           placeholder="e.g., 50000">
                                    <p class="text-xs text-slate-500 mt-1">Auto-approve if amount is below this limit</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="is_active" id="is_active" value="1" checked
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-slate-300 rounded">
                        <label for="is_active" class="ml-2 text-sm text-slate-700">Active</label>
                    </div>
                </div>

                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Save Trigger
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let editingTriggerId = null;
const triggers = @json($triggers);

function openCreateModal() {
    editingTriggerId = null;
    document.getElementById('modalTitle').textContent = 'Add Pre-Authorization Trigger';
    document.getElementById('triggerForm').action = '{{ route("settings.pre-authorization-triggers.store") }}';
    document.getElementById('formMethod').innerHTML = '';
    document.getElementById('triggerForm').reset();
    document.getElementById('is_active').checked = true;
    updateTriggerFields();
    document.getElementById('triggerModal').classList.remove('hidden');
}

function openEditModal(triggerId) {
    const trigger = triggers.find(t => t.id === triggerId);
    if (!trigger) return;

    editingTriggerId = triggerId;
    document.getElementById('modalTitle').textContent = 'Edit Pre-Authorization Trigger';
    document.getElementById('triggerForm').action = `{{ url('settings/pre-authorization-triggers') }}/${triggerId}`;
    document.getElementById('formMethod').innerHTML = '@method("PUT")';
    
    document.getElementById('trigger_name').value = trigger.trigger_name;
    document.getElementById('description').value = trigger.description || '';
    document.getElementById('trigger_type').value = trigger.trigger_type;
    document.getElementById('priority').value = trigger.priority;
    document.getElementById('service_category_id').value = trigger.service_category_id || '';
    document.getElementById('cost_threshold').value = trigger.cost_threshold || '';
    document.getElementById('keywords_text').value = trigger.keywords ? trigger.keywords.join('\n') : '';
    document.getElementById('auto_create_preauth').checked = trigger.auto_create_preauth;
    document.getElementById('require_manual_approval').checked = trigger.require_manual_approval;
    document.getElementById('auto_approval_limit').value = trigger.auto_approval_limit || '';
    document.getElementById('is_active').checked = trigger.is_active;
    
    updateTriggerFields();
    toggleAutoApprovalFields();
    document.getElementById('triggerModal').classList.remove('hidden');
}

function updateTriggerFields() {
    const triggerType = document.getElementById('trigger_type').value;
    
    // Show/hide fields based on trigger type
    document.getElementById('costThresholdField').style.display = 
        ['high_cost_service', 'cost_threshold'].includes(triggerType) ? 'block' : 'none';
    
    document.getElementById('keywordsField').style.display = 
        triggerType === 'keyword_match' ? 'block' : 'none';
    
    document.getElementById('serviceCategoryField').style.display = 
        triggerType === 'service_category' ? 'block' : 'block'; // Always show, but can be optional
}

function toggleAutoApprovalFields() {
    const autoCreate = document.getElementById('auto_create_preauth').checked;
    document.getElementById('autoApprovalFields').classList.toggle('hidden', !autoCreate);
}

function closeModal() {
    document.getElementById('triggerModal').classList.add('hidden');
    editingTriggerId = null;
}

// Process keywords before submit
document.getElementById('triggerForm').addEventListener('submit', function(e) {
    const keywordsText = document.getElementById('keywords_text').value;
    if (keywordsText) {
        const keywords = keywordsText.split('\n').map(k => k.trim()).filter(k => k);
        // Create hidden input for keywords array
        const keywordsInput = document.createElement('input');
        keywordsInput.type = 'hidden';
        keywordsInput.name = 'keywords';
        keywordsInput.value = JSON.stringify(keywords);
        this.appendChild(keywordsInput);
    }
});

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('triggerModal');
    if (event.target == modal) {
        closeModal();
    }
}

// Initialize on page load
updateTriggerFields();
</script>
@endsection
