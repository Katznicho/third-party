<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InsurerStore extends Model
{
    protected $table = 'insurer_stores';

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
