<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CryptoQuote extends Model
{
    use HasFactory;

    protected $fillable = [
        'crypto_id',
        'price',
        'volume_24h',
        'percent_change_24h',
        'market_cap',
        'source_timestamp'
    ];
}
