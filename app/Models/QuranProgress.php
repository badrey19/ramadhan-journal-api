<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuranProgress extends Model
{
    //
    protected $fillable = [
        'user_id',
        'juz_number',
    ];
}
