<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class AdminAccount extends Model
{
    protected $table = 'admin_account';

    protected $fillable = ['email', 'password'];

    protected $hidden = ['password'];

    public function setPasswordAttribute(string $value): void
    {
        $this->attributes['password'] = str_starts_with($value, '$2y$') || str_starts_with($value, '$argon')
            ? $value
            : Hash::make($value);
    }
}
