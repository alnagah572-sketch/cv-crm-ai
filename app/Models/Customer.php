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
        'cv_path',
        'cv_original_name',
        'cv_analysis_status',
        'cv_analyzed_at',
        'cv_ai_data',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cv_ai_data' => 'array',
        'cv_analyzed_at' => 'datetime',
    ];
}
