<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RolAction extends Model
{
    protected $fillable = [
        'created_at',
        'updated_at',
        'id_rol',
        'id_action'
    ];

    protected $table = 'roles_actions';
    protected $primaryKey = ['id_rol', 'id_action'];
    public $incrementing = false;
}
