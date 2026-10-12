<?php

namespace Tests\Feature\Models;

use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_the_four_fixed_roles(): void
    {
        $this->seed(RoleSeeder::class);

        $this->assertDatabaseHas('roles', ['code' => 'admin']);
        $this->assertDatabaseHas('roles', ['code' => 'teacher']);
        $this->assertDatabaseHas('roles', ['code' => 'student']);
        $this->assertDatabaseHas('roles', ['code' => 'parent']);
    }
}
