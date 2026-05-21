<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_home_route_serves_the_ziifra_landing_page(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('ZIIFRA', false);
    }
}
