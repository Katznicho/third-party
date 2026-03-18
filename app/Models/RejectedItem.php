<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RejectedItem extends Model
{
    protected $fillable = [
        'insurance_authorization_id',
        'item_name',
        'item_code',
        'amount',
        'reason_scope',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function insuranceAuthorization(): BelongsTo
    {
        return $this->belongsTo(InsuranceAuthorization::class);
    }
}

