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
        'visit_date',
        'expiry_at',
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

    // Check if visit is still valid
    public function isValid()
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->expiry_at && now()->isAfter($this->expiry_at)) {
            $this->update(['status' => 'expired']);
            return false;
        }

        return true;
    }
}
