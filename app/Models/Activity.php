<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'target',
        'unit',
        'for_date',
        'is_completed',
    ];
    
    // Pastikan casting untuk boolean dan integer agar Flutter tidak bingung
    protected $casts = [
        'is_completed' => 'boolean',
        'target' => 'integer',
    ];

    public function logs()
    {
        return $this->hasMany(ActivityLog::class);
    }
}