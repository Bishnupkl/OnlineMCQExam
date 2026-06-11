<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class Teacher extends Model
{
    protected $table = 'teacher_reg';

    protected $primaryKey = 't_id';

    protected $fillable = [
        't_name',
        't_gender',
        't_address',
        't_phone',
        't_email',
        't_password',
        'subject',
        'rdate',
        'permission',
        'salting_value',
    ];

    protected $hidden = ['t_password', 'salting_value'];

    public function setTPasswordAttribute(string $value): void
    {
        $this->attributes['t_password'] = str_starts_with($value, '$2y$') || str_starts_with($value, '$argon')
            ? $value
            : Hash::make($value);
    }
}
