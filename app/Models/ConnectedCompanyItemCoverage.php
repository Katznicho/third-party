<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ConnectedCompanyItemCoverage extends Model
{
    public const REASON_SCOPE_PARTIAL = 'partial_coverage';

    protected $fillable = [
        'insurance_company_id',
        'business_connection_id',
        'service_code',
        'coverage_percent',
        'reason',
        'is_active',
    ];

    protected $casts = [
        'coverage_percent' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function insuranceCompany()
    {
        return $this->belongsTo(InsuranceCompany::class);
    }

    public function businessConnection()
    {
        return $this->belongsTo(BusinessConnection::class, 'business_connection_id');
    }

    /**
     * @return Collection<string, self> keyed by lowercase service_code
     */
    public static function activeMapForConnection(int $insuranceCompanyId, int $businessConnectionId): Collection
    {
        return static::query()
            ->where('insurance_company_id', $insuranceCompanyId)
            ->where('business_connection_id', $businessConnectionId)
            ->where('is_active', true)
            ->whereNotNull('service_code')
            ->get()
            ->keyBy(fn (self $row) => mb_strtolower(trim((string) $row->service_code)));
    }

    public static function normalizePercent(float $value): float
    {
        return round(max(0.0, min(100.0, $value)), 2);
    }
}
