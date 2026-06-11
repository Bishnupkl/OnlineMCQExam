<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notice extends Model
{
    protected $table = 'notice';

    protected $primaryKey = 'n_id';

    protected $fillable = ['n_date', 'n_heading', 'n_text', 'n_description'];
}
