<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InsurerSupply extends Model
{
    protected $table = 'insurer_supplies';

    protected $fillable = [
        'insurance_company_id',
        'name',
        'description',
    ];

    public function insuranceCompany(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompany::class);
    }
}
