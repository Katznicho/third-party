<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InsurerQualification extends Model
{
    protected $table = 'insurer_qualifications';

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
        return $this->hasMany(User::class, 'qualification_id');
    }
}
