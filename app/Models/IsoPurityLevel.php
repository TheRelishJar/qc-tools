<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IsoPurityLevel extends Model
{
    protected $fillable = [
        'iso_class_type',
        'level',
        'purity_description',
    ];
}
