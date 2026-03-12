<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientLocalExclusion extends Model
{
    use HasFactory;

    protected $fillable = [
        'insurance_company_id',
        'client_id',
        'reason',
    ];

    public function insuranceCompany(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompany::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}

