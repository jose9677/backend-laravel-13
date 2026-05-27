<?php

namespace Database\Seeders;

use App\Models\Action;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ActionSedder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Action::create([
            'description' => 'CONSULTAR-USUARIOS',
            'active' => true,
            'id_module' => 1,
            'route' => 'api/auth',
            'method' => 'GET'
        ]);

        Action::create([
            'description' => 'CONSULTAR-USUARIO-POR-ID',
            'active' => true,
            'id_module' => 1,
            'route' => 'api/auth/{identity}',
            'method' => 'GET'
        ]);
    }
}
