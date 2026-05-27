<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAction extends Model
{
    protected $fillable = [
        'created_at',
        'updated_at',
        'identity',
        'id_action'
    ];

    protected $table = 'users_actions';
    protected $primaryKey = ['identity', 'id_action'];
    public $incrementing = false;
}
