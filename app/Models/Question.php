<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $table = 'question_table';

    protected $primaryKey = 'q_id';

    protected $fillable = [
        'question',
        'choice1',
        'choice2',
        'choice3',
        'choice4',
        'correct_ans',
        'mark',
        'uploaded_by',
    ];

    protected $hidden = ['correct_ans'];
}
