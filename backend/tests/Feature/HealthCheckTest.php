<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_up_endpoint_responde_ok(): void
    {
        $this->get('/up')->assertStatus(200);
    }
}
