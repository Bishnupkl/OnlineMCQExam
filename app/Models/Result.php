<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Result extends Model
{
    protected $table = 'result';

    protected $fillable = [
        'email',
        'ques_attempted',
        'mark_obtained',
        'right_answer',
        'wrong_answer',
        'status',
    ];
}
