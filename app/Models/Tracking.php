<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tracking extends Model
{
    protected $fillable = [
        'login',
        'route',
        'data_sent',
        'method',
        'json_response',
        'code_response',
        'ip'
    ];

    protected $primaryKey = 'id_tracking';
    protected $table = "tracking";
}
