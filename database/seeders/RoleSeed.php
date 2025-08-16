<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeed extends Seeder
{
    public function run(): void
    {
        $now = now();

        $roles = [
            ['name' => 'admin',       'created_at' => $now, 'updated_at' => $now],
            ['name' => 'client',      'created_at' => $now, 'updated_at' => $now],
            ['name' => 'prestataire', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'entreprise',  'created_at' => $now, 'updated_at' => $now],
        ];

        DB::table('roles')->insert($roles);
    }
}
