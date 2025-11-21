<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IsoConfiguration extends Model
{
    protected $fillable = [
        'iso_class',
        'particulate_class',
        'water_class',
        'oil_class',
        'compressor',
        'qas1',
        'qas2',
        'qas3',
        'qas4',
        'qas5',
        'qas6',
        'qas7',
        'qas8',
        'qas9',
    ];
}
