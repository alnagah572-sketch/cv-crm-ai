<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'full_name',
        'phone',
        'email',
        'city',
        'country',
        'service',
        'source',
        'price',
        'status',
        'notes',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];
}
