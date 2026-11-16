<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_the_application_homepage_returns_a_successful_response(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('CareDesk');
    }
}
