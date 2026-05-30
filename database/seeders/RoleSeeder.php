<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = ['client', 'provider', 'admin'];

        foreach ($roles as $role) {
            // Solo inserta si no existe
            DB::table('roles')->updateOrInsert(
                ['name' => $role],
                ['name' => $role]
            );
        }
    }
}
