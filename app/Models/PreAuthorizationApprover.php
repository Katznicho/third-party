<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreAuthorizationApprover extends Model
{
    protected $fillable = ['insurance_company_id', 'user_id', 'level'];

    public function insuranceCompany()
    {
        return $this->belongsTo(InsuranceCompany::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
