<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_reference', 'invoice_id', 'policy_id', 'client_id', 'payment_type',
        'amount', 'paid_amount', 'balance_amount', 'payment_method', 'bank_name',
        'account_number', 'mobile_money_provider', 'mobile_money_number', 'transaction_id',
        'cheque_number', 'cheque_date', 'status', 'payment_date', 'received_date',
        'cleared_date', 'processed_at', 'receipt_number', 'receipt_path', 'receipt_generated_at',
        'payment_notes', 'failure_reason', 'processed_by', 'payment_metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
        'cheque_date' => 'date',
        'payment_date' => 'date',
        'received_date' => 'date',
        'cleared_date' => 'date',
        'processed_at' => 'datetime',
        'receipt_generated_at' => 'datetime',
        'payment_metadata' => 'array',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(Policy::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
