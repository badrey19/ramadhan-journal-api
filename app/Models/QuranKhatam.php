<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuranKhatam extends Model
{
    //
    protected $fillable = [
        'user_id',
        'khatam_number',
        'completed_at',
    ];

    
}
