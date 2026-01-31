<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    /**
     * Test health endpoint returns OK status.
     */
    public function test_health_endpoint_returns_ok_status(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200)
            ->assertJson(['status' => 'ok']);
    }

    /**
     * Test health endpoint returns JSON content type.
     */
    public function test_health_endpoint_returns_json_content_type(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * Test health endpoint structure.
     */
    public function test_health_endpoint_has_correct_structure(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertJsonStructure(['status']);
    }
}
