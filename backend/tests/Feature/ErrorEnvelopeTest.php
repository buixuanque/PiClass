<?php

namespace Tests\Feature;

use Tests\TestCase;

class ErrorEnvelopeTest extends TestCase
{
    public function test_404_on_api_routes_uses_the_error_envelope(): void
    {
        $response = $this->getJson('/api/v1/does-not-exist');

        $response->assertStatus(404);
        $response->assertJsonStructure(['error' => ['code', 'message']]);
    }
}
