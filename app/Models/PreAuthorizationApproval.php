<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreAuthorizationApproval extends Model
{
    protected $fillable = ['pre_authorization_id', 'level', 'user_id', 'action', 'notes', 'acted_at'];

    protected function casts(): array
    {
        return [
            'acted_at' => 'datetime',
        ];
    }

    public function preAuthorization()
    {
        return $this->belongsTo(PreAuthorization::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
