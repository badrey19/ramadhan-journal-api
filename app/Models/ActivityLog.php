<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    
    //
    protected $fillable = [
        'user_id',
        'activity_id',
        'log_date',
        'is_completed',
    ];

    public function activity() {
        return $this->belongsTo(Activity::class);
    }
}
