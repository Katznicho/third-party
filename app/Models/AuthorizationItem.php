<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuthorizationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'pre_authorization_id', 'item_code', 'item_name', 'item_description', 'item_category',
        'quantity', 'unit', 'unit_price', 'total_amount', 'authorization_status',
        'approved_amount', 'authorization_notes', 'provider_name', 'provider_code', 'visit_reference',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',
    ];

    public function preAuthorization(): BelongsTo
    {
        return $this->belongsTo(PreAuthorization::class);
    }
}
