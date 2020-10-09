<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Log extends Model
{

    protected $table = 'error_logs';
    protected $guarded = [];
}
