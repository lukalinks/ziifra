<?php

namespace Tests\Feature;

use Tests\TestCase;

class LandingPageTest extends TestCase
{
    public function test_landing_page_displays_ziifra_branding_in_english(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('ZIIFRA', false);
        $response->assertSee('Kosovo', false);
        $response->assertSee('Start free trial', false);
        $response->assertSee('lang="en"', false);
    }

    public function test_privacy_and_terms_pages_are_available(): void
    {
        $this->get(route('privacy'))->assertOk()->assertSee('Privacy Policy');
        $this->get(route('terms'))->assertOk()->assertSee('Terms of Service');
    }
}
