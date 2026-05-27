<?php

namespace Database\Seeders;

use App\Models\RolAction;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RolActionSedder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //ADMINISTRADOR...
        RolAction::create([
            'id_rol' => 1,
            'id_action' => 1
        ]);

        RolAction::create([
            'id_rol' => 1,
            'id_action' => 2
        ]);

        //ANALISTA...
        RolAction::create([
            'id_rol' => 2,
            'id_action' => 1
        ]);
    }
}
