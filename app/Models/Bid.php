<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bid extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    public function quote()
    {
        return $this->belongsTo(Quote::class);
    }

    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id');
    }
   
}
