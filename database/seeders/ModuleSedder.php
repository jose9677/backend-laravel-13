<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ModuleSedder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Module::create([
            'description' => 'MODULO-1',
            'active' => true,
        ]);

        Module::create([
            'description' => 'MODULO-2',
            'active' => true,
        ]);

        Module::create([
            'description' => 'MODULO-3',
            'active' => true,
        ]);
    }
}
