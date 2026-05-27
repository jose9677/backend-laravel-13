<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $fillable = [
        'id_module',
        'description',
        'active',
        'created_at',
        'updated_at'
    ];

    protected $table = 'modules';
    protected $primaryKey = 'id_module';
    public $incrementing = false;
}
