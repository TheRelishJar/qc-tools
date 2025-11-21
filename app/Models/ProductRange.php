<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductRange extends Model
{
    protected $fillable = [
        'water_class',
        'product_range',
        'dewpoint',
        'min_flow',
        'max_flow',
        'inlet_filters',
        'outlet_filters',
        'comment',
    ];

    protected $casts = [
        'min_flow' => 'decimal:2',
        'max_flow' => 'decimal:2',
    ];
}
