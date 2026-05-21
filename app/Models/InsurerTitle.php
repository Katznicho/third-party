<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InsurerTitle extends Model
{
    protected $table = 'insurer_titles';

    protected $fillable = [
        'insurance_company_id',
        'name',
        'description',
    ];

    public function insuranceCompany(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompany::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'title_id');
    }

    /**
     * Salutation options for client forms (name => name). Falls back to common defaults.
     *
     * @return array<string, string>
     */
    public static function optionsForCompany(?int $insuranceCompanyId): array
    {
        $defaults = ['Mr' => 'Mr', 'Mrs' => 'Mrs', 'Miss' => 'Miss', 'Dr' => 'Dr'];

        if (! $insuranceCompanyId) {
            return $defaults;
        }

        $rows = static::query()
            ->where('insurance_company_id', $insuranceCompanyId)
            ->orderBy('name')
            ->pluck('name', 'name');

        return $rows->isEmpty() ? $defaults : $rows->all();
    }
}
