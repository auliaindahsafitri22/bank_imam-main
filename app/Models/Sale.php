<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $table = 'sales';

    protected $guarded = [];

    protected $casts = [
        'items'        => 'array',
        'total_weight' => 'float',
        'total_price'  => 'float',
    ];
}