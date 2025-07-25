<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{

    use HasFactory;

    protected $guarded = ['id'];

    // ✅ Auto set status based on total_quotes
    protected static function booted()
    {
        static::creating(function ($plan) {
            $plan->status = $plan->total_quotes === 0 ? 'Inactive' : 'Active';
        });

        static::updating(function ($plan) {
            $plan->status = $plan->total_quotes === 0 ? 'Inactive' : 'Active';
        });
    }
}
