<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['code' => 'admin', 'name' => 'Admin'],
            ['code' => 'teacher', 'name' => 'Teacher'],
            ['code' => 'student', 'name' => 'Student'],
            ['code' => 'parent', 'name' => 'Parent'],
        ] as $role) {
            Role::firstOrCreate(['code' => $role['code']], $role);
        }
    }
}
