<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Action extends Model
{
     protected $fillable = [
        'description',
        'active',
        'created_at',
        'updated_at',
        'id_module',
        'route',
        'method'
    ];

    protected $table = 'actions';
    protected $primaryKey = 'id_action';
    public $incrementing = false;
}
