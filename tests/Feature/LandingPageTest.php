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
        $response->assertSee('Start free trial', false);
        $response->assertSee('Start free trial', false);
        $response->assertSee('lang="en"', false);
    }

    public function test_landing_page_displays_albanian_when_locale_is_sq(): void
    {
        $response = $this->withSession(['locale' => 'sq'])->get(route('home'));

        $response->assertOk();
        $response->assertSee('Provë falas', false);
        $response->assertSee('Veçoritë', false);
        $response->assertSee('Pagat', false);
        $response->assertSee('lang="sq"', false);
    }

    public function test_privacy_and_terms_pages_are_available(): void
    {
        $this->get(route('privacy'))->assertOk()->assertSee('Privacy Policy');
        $this->get(route('terms'))->assertOk()->assertSee('Terms of Service');
    }
}
