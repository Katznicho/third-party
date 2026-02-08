@extends('layouts.dashboard')

@section('title', 'Coverage Decision Matrix')
@section('page-title', 'Coverage Decision Matrix')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Coverage Decision Matrix</h1>
            <p class="text-slate-600 mt-1">Configure automatic decision rules for coverage matching</p>
        </div>
        <button onclick="openCreateModal()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            + Add Rule
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

    <!-- Rules Table -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Priority</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Rule Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Condition Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Action</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-200">
                @forelse($rules as $rule)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">{{ $rule->priority }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-slate-900">{{ $rule->rule_name }}</div>
                            @if($rule->description)
                                <div class="text-xs text-slate-500">{{ Str::limit($rule->description, 50) }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-slate-900">{{ ucwords(str_replace('_', ' ', $rule->condition_type)) }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full
                                {{ $rule->action === 'auto_reject' ? 'bg-red-100 text-red-800' : 
                                   ($rule->action === 'flag_for_review' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800') }}">
                                {{ ucwords(str_replace('_', ' ', $rule->action)) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $rule->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ $rule->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button onclick="openEditModal({{ $rule->id }})" class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                            <form action="{{ route('settings.coverage-decision-matrix.destroy', $rule) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this rule?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-sm text-slate-500">
                            No decision rules configured. Click "Add Rule" to create one.
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
<div id="ruleModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-slate-900 mb-4" id="modalTitle">Add Decision Rule</h3>
            <form id="ruleForm" method="POST">
                @csrf
                <div id="formMethod" style="display: none;"></div>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Rule Name *</label>
                        <input type="text" name="rule_name" id="rule_name" required
                               placeholder="e.g., OPD Not Covered, High Cost Rejection"
                               class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
                        <textarea name="description" id="description" rows="2"
                                  placeholder="Brief description of what this rule does..."
                                  class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Condition Type *</label>
                            <select name="condition_type" id="condition_type" required onchange="updateConditionConfig()"
                                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="service_category_not_covered">Service Category Not Covered</option>
                                <option value="service_category_coverage_limit_exceeded">Coverage Limit Exceeded</option>
                                <option value="cost_threshold_exceeded">Cost Threshold Exceeded</option>
                                <option value="keyword_match">Keyword Match</option>
                                <option value="procedure_type">Procedure Type</option>
                                <option value="visit_type_not_covered">Visit Type Not Covered</option>
                                <option value="custom_condition">Custom Condition</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Action *</label>
                            <select name="action" id="action" required
                                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="auto_reject">Auto Reject</option>
                                <option value="flag_for_review">Flag for Review</option>
                                <option value="require_pre_authorization">Require Pre-Authorization</option>
                            </select>
                        </div>
                    </div>

                    <!-- Dynamic Condition Config -->
                    <div id="condition_config_section">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Condition Configuration *</label>
                        <div id="condition_config_input" class="space-y-2">
                            <!-- This will be dynamically populated based on condition_type -->
                        </div>
                        <input type="hidden" name="condition_config" id="condition_config" value="{}">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Priority *</label>
                        <input type="number" name="priority" id="priority" min="1" max="1000" value="100" required
                               placeholder="e.g., 10 (lower = higher priority)"
                               class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="text-xs text-slate-500 mt-1">Lower number = higher priority (1 is highest, 1000 is lowest). Example: 10, 20, 30</p>
                    </div>

                        <div class="flex items-center pt-6">
                            <input type="checkbox" name="is_active" id="is_active" value="1" checked
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-slate-300 rounded">
                            <label for="is_active" class="ml-2 text-sm text-slate-700">Active</label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Rejection Message</label>
                        <textarea name="rejection_message" id="rejection_message" rows="2"
                                  class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                  placeholder="e.g., This service is not covered under your policy. Please contact your insurance provider for more information."></textarea>
                        <p class="text-xs text-slate-500 mt-1">Message shown to users when this rule triggers. Leave empty to use default message.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Review Notes Template</label>
                        <textarea name="review_notes_template" id="review_notes_template" rows="3"
                                  class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                  placeholder="e.g., Policy {policy_number} - Service {service_category} for amount {amount} requires review. Available placeholders: {policy_number}, {service_category}, {amount}"></textarea>
                        <p class="text-xs text-slate-500 mt-1">Template for review notes. Available placeholders: <code class="bg-slate-100 px-1 rounded">{policy_number}</code>, <code class="bg-slate-100 px-1 rounded">{service_category}</code>, <code class="bg-slate-100 px-1 rounded">{amount}</code></p>
                    </div>
                </div>

                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Save Rule
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let editingRuleId = null;
const rules = @json($rules);

function updateConditionConfig() {
    const conditionType = document.getElementById('condition_type').value;
    const configInput = document.getElementById('condition_config_input');
    const configHidden = document.getElementById('condition_config');
    
    let html = '';
    let config = {};
    
    switch(conditionType) {
        case 'service_category_not_covered':
            html = `
                <label class="block text-xs text-slate-600 mb-1">Service Category IDs (comma-separated)</label>
                <input type="text" id="config_service_category_ids" 
                       placeholder="e.g., 1, 2, 3 (IDs of service categories to reject)"
                       class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                       onchange="updateConfigJson()">
                <p class="text-xs text-slate-500 mt-1">Enter service category IDs separated by commas</p>
            `;
            break;
            
        case 'service_category_coverage_limit_exceeded':
            html = `
                <label class="block text-xs text-slate-600 mb-1">Threshold Percentage</label>
                <input type="number" id="config_threshold_percentage" min="0" max="100" 
                       placeholder="e.g., 90 (trigger when 90% of coverage limit is used)"
                       class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                       onchange="updateConfigJson()">
                <p class="text-xs text-slate-500 mt-1">Percentage of coverage limit used before triggering (0-100)</p>
            `;
            break;
            
        case 'cost_threshold_exceeded':
            html = `
                <label class="block text-xs text-slate-600 mb-1">Cost Threshold (UGX)</label>
                <input type="number" id="config_cost_threshold" min="0" step="0.01"
                       placeholder="e.g., 500000 (trigger when cost exceeds UGX 500,000)"
                       class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                       onchange="updateConfigJson()">
                <p class="text-xs text-slate-500 mt-1">Minimum cost amount to trigger this rule</p>
            `;
            break;
            
        case 'keyword_match':
            html = `
                <label class="block text-xs text-slate-600 mb-1">Keywords (one per line or comma-separated)</label>
                <textarea id="config_keywords" rows="3"
                          placeholder="e.g., cosmetic, plastic surgery, aesthetic&#10;or: cosmetic, plastic surgery, aesthetic"
                          class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                          onchange="updateConfigJson()"></textarea>
                <p class="text-xs text-slate-500 mt-1">Enter keywords that should trigger this rule (case-insensitive)</p>
            `;
            break;
            
        case 'procedure_type':
            html = `
                <label class="block text-xs text-slate-600 mb-1">Procedure Types (comma-separated)</label>
                <input type="text" id="config_procedure_types"
                       placeholder="e.g., surgery, consultation, lab_test"
                       class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                       onchange="updateConfigJson()">
                <p class="text-xs text-slate-500 mt-1">Enter procedure types separated by commas</p>
            `;
            break;
            
        case 'visit_type_not_covered':
            html = `
                <label class="block text-xs text-slate-600 mb-1">Visit Types (comma-separated)</label>
                <input type="text" id="config_visit_types"
                       placeholder="e.g., emergency, walk_in, referral"
                       class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                       onchange="updateConfigJson()">
                <p class="text-xs text-slate-500 mt-1">Enter visit types separated by commas</p>
            `;
            break;
            
        default:
            html = `
                <label class="block text-xs text-slate-600 mb-1">Custom Configuration (JSON)</label>
                <textarea id="config_custom" rows="4"
                          placeholder='{"key": "value"}'
                          class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono text-sm"
                          onchange="updateConfigJson()"></textarea>
                <p class="text-xs text-slate-500 mt-1">Enter JSON configuration for custom condition</p>
            `;
    }
    
    configInput.innerHTML = html;
    updateConfigJson();
}

function updateConfigJson() {
    const conditionType = document.getElementById('condition_type').value;
    const configHidden = document.getElementById('condition_config');
    let config = {};
    
    switch(conditionType) {
        case 'service_category_not_covered':
            const categoryIds = document.getElementById('config_service_category_ids')?.value;
            if (categoryIds) {
                config.service_category_ids = categoryIds.split(',').map(id => parseInt(id.trim())).filter(id => !isNaN(id));
            }
            break;
            
        case 'service_category_coverage_limit_exceeded':
            const threshold = document.getElementById('config_threshold_percentage')?.value;
            if (threshold) {
                config.threshold_percentage = parseInt(threshold);
            }
            break;
            
        case 'cost_threshold_exceeded':
            const costThreshold = document.getElementById('config_cost_threshold')?.value;
            if (costThreshold) {
                config.cost_threshold = parseFloat(costThreshold);
            }
            break;
            
        case 'keyword_match':
            const keywordsInput = document.getElementById('config_keywords')?.value;
            if (keywordsInput) {
                // Support both comma-separated and newline-separated
                const keywords = keywordsInput.split(/[,\n]/).map(k => k.trim()).filter(k => k);
                config.keywords = keywords;
            }
            break;
            
        case 'procedure_type':
            const procedureTypes = document.getElementById('config_procedure_types')?.value;
            if (procedureTypes) {
                config.procedure_types = procedureTypes.split(',').map(t => t.trim()).filter(t => t);
            }
            break;
            
        case 'visit_type_not_covered':
            const visitTypes = document.getElementById('config_visit_types')?.value;
            if (visitTypes) {
                config.visit_types = visitTypes.split(',').map(t => t.trim()).filter(t => t);
            }
            break;
            
        default:
            const customConfig = document.getElementById('config_custom')?.value;
            if (customConfig) {
                try {
                    config = JSON.parse(customConfig);
                } catch(e) {
                    console.error('Invalid JSON:', e);
                }
            }
    }
    
    configHidden.value = JSON.stringify(config);
}

function openCreateModal() {
    editingRuleId = null;
    document.getElementById('modalTitle').textContent = 'Add Decision Rule';
    document.getElementById('ruleForm').action = '{{ route("settings.coverage-decision-matrix.store") }}';
    document.getElementById('formMethod').innerHTML = '';
    document.getElementById('ruleForm').reset();
    document.getElementById('is_active').checked = true;
    document.getElementById('condition_config').value = '{}';
    updateConditionConfig();
    document.getElementById('ruleModal').classList.remove('hidden');
}

function openEditModal(ruleId) {
    const rule = rules.find(r => r.id === ruleId);
    if (!rule) return;

    editingRuleId = ruleId;
    document.getElementById('modalTitle').textContent = 'Edit Decision Rule';
    document.getElementById('ruleForm').action = `{{ url('settings/coverage-decision-matrix') }}/${ruleId}`;
    document.getElementById('formMethod').innerHTML = '@method("PUT")';
    
    document.getElementById('rule_name').value = rule.rule_name;
    document.getElementById('description').value = rule.description || '';
    document.getElementById('condition_type').value = rule.condition_type;
    document.getElementById('action').value = rule.action;
    document.getElementById('priority').value = rule.priority;
    document.getElementById('is_active').checked = rule.is_active;
    document.getElementById('rejection_message').value = rule.rejection_message || '';
    document.getElementById('review_notes_template').value = rule.review_notes_template || '';
    
    // Update condition config UI
    updateConditionConfig();
    
    // Populate condition config fields
    const config = rule.condition_config || {};
    setTimeout(() => {
        switch(rule.condition_type) {
            case 'service_category_not_covered':
                if (config.service_category_ids) {
                    document.getElementById('config_service_category_ids').value = config.service_category_ids.join(', ');
                }
                break;
            case 'service_category_coverage_limit_exceeded':
                if (config.threshold_percentage) {
                    document.getElementById('config_threshold_percentage').value = config.threshold_percentage;
                }
                break;
            case 'cost_threshold_exceeded':
                if (config.cost_threshold) {
                    document.getElementById('config_cost_threshold').value = config.cost_threshold;
                }
                break;
            case 'keyword_match':
                if (config.keywords) {
                    document.getElementById('config_keywords').value = config.keywords.join('\n');
                }
                break;
            case 'procedure_type':
                if (config.procedure_types) {
                    document.getElementById('config_procedure_types').value = config.procedure_types.join(', ');
                }
                break;
            case 'visit_type_not_covered':
                if (config.visit_types) {
                    document.getElementById('config_visit_types').value = config.visit_types.join(', ');
                }
                break;
            default:
                if (config) {
                    document.getElementById('config_custom').value = JSON.stringify(config, null, 2);
                }
        }
        updateConfigJson();
    }, 100);
    
    document.getElementById('ruleModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('ruleModal').classList.add('hidden');
    editingRuleId = null;
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('ruleModal');
    if (event.target == modal) {
        closeModal();
    }
}
</script>
@endsection
