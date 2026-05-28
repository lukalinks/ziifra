<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Services\InvitationService;
use App\Services\RegisterOrganizationService;
use App\Support\OrganizationLogo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SocialMetaTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_includes_open_graph_tags_and_default_share_image(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('<meta property="og:title"', false)
            ->assertSee('<meta property="og:description"', false)
            ->assertSee('<meta property="og:image"', false)
            ->assertSee('<meta name="twitter:card" content="summary_large_image">', false)
            ->assertSee('og/ziifra-share.png', false);
    }

    public function test_public_org_logo_route_serves_uploaded_logo(): void
    {
        Storage::fake('local');

        $owner = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $organization = $owner['organization'];
        $organization->update([
            'logo_path' => OrganizationLogo::store($organization, UploadedFile::fake()->image('logo.png', 200, 200)),
        ]);

        $this->get(route('org.brand.logo', $organization))
            ->assertOk();
    }

    public function test_public_org_logo_redirects_to_default_when_missing(): void
    {
        $owner = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $this->get(route('org.brand.logo', $owner['organization']))
            ->assertRedirect();
    }

    public function test_invitation_page_uses_organization_branding_in_meta(): void
    {
        $owner = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $invitation = app(InvitationService::class)->send(
            $owner['organization'],
            $owner['user'],
            'newhire@acme.test',
            OrganizationRole::Employee,
        );

        Storage::fake('local');
        $organization = $owner['organization'];
        $organization->update([
            'logo_path' => OrganizationLogo::store($organization, UploadedFile::fake()->image('logo.png', 400, 400)),
        ]);

        $this->get(route('invitations.accept', $invitation->token))
            ->assertOk()
            ->assertSee('property="og:site_name" content="Acme SHPK"', false)
            ->assertSee(route('org.brand.logo', $organization, absolute: true), false);
    }

    public function test_workspace_dashboard_includes_org_site_name_in_open_graph(): void
    {
        $owner = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $this->actingAs($owner['user'])
            ->withSession(['current_organization_id' => $owner['organization']->id])
            ->get($this->workspaceRoute('dashboard', $owner['organization']))
            ->assertOk()
            ->assertSee('property="og:site_name" content="Acme SHPK"', false);
    }
}
