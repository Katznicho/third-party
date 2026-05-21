@php
    $fieldName = $fieldName ?? 'title';
    $insurerTitleOptions = $insurerTitleOptions ?? \App\Models\InsurerTitle::optionsForCompany(auth()->user()?->insurance_company_id);
    $selectedTitle = $selectedTitle ?? '';
@endphp
<option value="">Select Title</option>
@foreach($insurerTitleOptions as $value => $label)
    <option value="{{ $value }}" {{ (string) old($fieldName, $selectedTitle) === (string) $value ? 'selected' : '' }}>{{ $label }}</option>
@endforeach
