<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class Student extends Model
{
    protected $table = 'stu_reg';

    protected $fillable = [
        'name',
        'address',
        'fatname',
        'dob',
        'phone',
        'email',
        'password',
        'reg_date',
        'gender',
        'exam_status',
        'salting_value',
    ];

    protected $hidden = ['password', 'salting_value'];

    protected function casts(): array
    {
        return [
            'dob' => 'date',
            'reg_date' => 'date',
        ];
    }

    public function setPasswordAttribute(string $value): void
    {
        $this->attributes['password'] = str_starts_with($value, '$2y$') || str_starts_with($value, '$argon')
            ? $value
            : Hash::make($value);
    }
}
