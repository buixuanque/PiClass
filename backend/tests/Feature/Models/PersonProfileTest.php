<?php

namespace Tests\Feature\Models;

use App\Models\PersonProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PersonProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_person_profile_with_uuid(): void
    {
        $profile = PersonProfile::create([
            'full_name' => 'Nguyen Van A',
            'email' => 'a@example.com',
        ]);

        $this->assertTrue(Str::isUuid($profile->id));
        $this->assertDatabaseHas('person_profiles', [
            'id' => $profile->id,
            'full_name' => 'Nguyen Van A',
        ]);
    }
}
