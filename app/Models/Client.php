<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'principal_member_id',
        'plan_id',
        'surname',
        'first_name',
        'other_names',
        'title',
        'id_passport_no',
        'gender',
        'tin',
        'date_of_birth',
        'marital_status',
        'height',
        'weight',
        'employer_name',
        'occupation',
        'nationality',
        'home_physical_address',
        'office_physical_address',
        'home_telephone',
        'office_telephone',
        'cell_phone',
        'whatsapp_line',
        'email',
        'relation_to_principal',
        'next_of_kin_surname',
        'next_of_kin_first_name',
        'next_of_kin_other_names',
        'next_of_kin_title',
        'next_of_kin_relation',
        'next_of_kin_id_passport_no',
        'next_of_kin_cell_phone',
        'next_of_kin_email',
        'next_of_kin_post_address',
        'next_of_kin_physical_address',
        'medical_history',
        'regular_medications',
        'has_deductible',
        'deductible_amount',
        'telemedicine_only',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'medical_history' => 'array',
            'regular_medications' => 'array',
            'has_deductible' => 'boolean',
            'deductible_amount' => 'decimal:2',
            'telemedicine_only' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    // Relationships
    public function principalMember(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'principal_member_id');
    }

    public function dependents(): HasMany
    {
        return $this->hasMany(Client::class, 'principal_member_id');
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(Policy::class, 'principal_member_id');
    }

    public function policies(): HasMany
    {
        return $this->hasMany(Policy::class, 'principal_member_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->surname} {$this->other_names}");
    }

    public function isPrincipal(): bool
    {
        return $this->type === 'principal';
    }

    public function isDependent(): bool
    {
        return $this->type === 'dependent';
    }
}
