<?php

namespace App\Models;

use App\Enums\ScaffoldStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ScaffoldRun extends Model
{
    use HasUuids;

    protected $fillable = [
        'manifest',
        'status',
        'output',
        'completed_at',
    ];

    protected $casts = [
        'manifest' => 'array',
        'status' => ScaffoldStatus::class,
        'completed_at' => 'datetime',
    ];
}
