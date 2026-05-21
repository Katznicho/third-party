<?php

namespace App\Http\Controllers\Concerns;

trait ManagesInsurerCompanyLookups
{
    protected function requireInsuranceCompany(): ?\Illuminate\Http\RedirectResponse
    {
        if (! auth()->user()->insurance_company_id) {
            return redirect()->route('dashboard')
                ->with('error', 'You must be associated with an insurance company to manage these settings.');
        }

        return null;
    }

    protected function authorizeCompanyRecord(object $record): void
    {
        if ((int) $record->insurance_company_id !== (int) auth()->user()->insurance_company_id) {
            abort(403);
        }
    }
}
