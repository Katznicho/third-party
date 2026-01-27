<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicalQuestionResponse extends Model
{
    protected $fillable = [
        'client_id',
        'medical_question_id',
        'response',
        'additional_info',
        'triggers_exclusion',
    ];

    protected $casts = [
        'additional_info' => 'array',
        'triggers_exclusion' => 'boolean',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(MedicalQuestion::class, 'medical_question_id');
    }
}
