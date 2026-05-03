<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AuthorizedVisit extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'insurance_company_id',
        'kashtre_client_id',
        'visit_id',
        'session_code',
        'visit_date',
        'expiry_at',
        'session_expires_at',
        'status',
        'services_category',
        'notes',
        'sync_data',
    ];

    protected function casts(): array
    {
        return [
            'visit_date' => 'date',
            'expiry_at' => 'datetime',
            'session_expires_at' => 'datetime',
            'sync_data' => 'array',
        ];
    }

    // Relationships
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function insuranceCompany()
    {
        return $this->belongsTo(InsuranceCompany::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForInsuranceCompany($query, $insuranceCompanyId)
    {
        return $query->where('insurance_company_id', $insuranceCompanyId);
    }

    public function scopeForClient($query, $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * Visit is valid until the earliest of: Kashtre visit expiry, insurer session expiry (visit date + authorization period days).
     */
    public function isValid()
    {
        if ($this->status !== 'active') {
            return false;
        }

        $deadlines = [];
        if ($this->expiry_at) {
            $deadlines[] = $this->expiry_at;
        }
        if ($this->session_expires_at) {
            $deadlines[] = $this->session_expires_at;
        }

        if ($deadlines !== []) {
            $effectiveEnd = collect($deadlines)->min();
            if (now()->isAfter($effectiveEnd)) {
                $this->update(['status' => 'expired']);
                return false;
            }
        }

        return true;
    }
}
