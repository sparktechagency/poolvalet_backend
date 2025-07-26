<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quote extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    // users -> quotes (one to many)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bids()
    {
        return $this->hasMany(Bid::class);
    }

    // // Quote.php model এ
    // public function getPhotosAttribute($value)
    // {
    //     $decoded = json_decode($value, true);

    //     if (is_string($decoded)) {
    //         $decoded = json_decode($decoded, true);
    //     }

    //     return $decoded;
    // }
}
