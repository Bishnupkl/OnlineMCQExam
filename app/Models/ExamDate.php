<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamDate extends Model
{
    protected $table = 'exam_date';

    protected $fillable = ['edate'];

    protected function casts(): array
    {
        return ['edate' => 'date'];
    }
}
