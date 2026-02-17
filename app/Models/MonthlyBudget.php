<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlyBudget extends Model
{
    //
    protected $fillable = ['user_id', 'month', 'salary_input', 'needs_balance', 'wants_balance', 'savings_balance'];
}
