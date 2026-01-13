<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number', 'policy_id', 'client_id', 'invoice_type', 'description',
        'invoice_date', 'due_date', 'paid_date', 'subtotal', 'tax_amount', 'discount_amount',
        'total_amount', 'paid_amount', 'balance_amount', 'status', 'billing_period_start',
        'billing_period_end', 'premium_amount', 'insurance_training_levy', 'stamp_duty',
        'payment_terms_days', 'payment_instructions', 'notes', 'pdf_path', 'pdf_generated_at',
        'created_by', 'line_items',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'paid_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
        'billing_period_start' => 'date',
        'billing_period_end' => 'date',
        'premium_amount' => 'decimal:2',
        'insurance_training_levy' => 'decimal:2',
        'stamp_duty' => 'decimal:2',
        'pdf_generated_at' => 'datetime',
        'line_items' => 'array',
    ];

    public function policy(): BelongsTo
    {
        return $this->belongsTo(Policy::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
