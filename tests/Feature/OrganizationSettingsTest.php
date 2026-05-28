<?php

namespace Tests\Feature;

use App\Enums\OrganizationLegalForm;
use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use App\Services\RegisterOrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesTwoOrganizations;
use Tests\TestCase;

class OrganizationSettingsTest extends TestCase
{
    use CreatesTwoOrganizations;
    use RefreshDatabase;

    public function test_owner_can_view_and_update_company_settings(): void
    {
        Storage::fake('local');

        $data = $this->createCompanyA();
        $organization = $data['organization'];

        $this->actingAsCompanyA($data['user'], $organization)
            ->get($this->workspaceRoute('settings.company.edit', $organization))
            ->assertOk()
            ->assertSee('Company identity');

        $this->actingAsCompanyA($data['user'], $organization)
            ->put($this->workspaceRoute('settings.company.update', $organization), array_merge(
                $this->companySettingsPayload($organization),
                [
                    'name' => 'Company A Updated',
                    'legal_name' => 'Company A SHPK',
                    'legal_form' => OrganizationLegalForm::Shpk->value,
                    'fiscal_number' => '601234567',
                    'registration_number' => '12345678',
                    'address_line_1' => 'Rr. Nëna Terezë 1',
                    'city' => 'Prishtinë',
                    'postal_code' => '10000',
                    'email' => 'info@company-a.test',
                    'hr_email' => 'hr@company-a.test',
                    'phone' => '+38344111222',
                    'website' => 'company-a.test',
                    'primary_color' => '#112233',
                    'accent_color' => '#aabbcc',
                    'brand_tagline' => 'Building the future',
                    'logo' => UploadedFile::fake()->image('logo.png'),
                ],
            ))
            ->assertRedirect($this->workspaceRoute('settings.company.edit', $organization))
            ->assertSessionHas('status');

        $organization->refresh();

        $this->assertSame('Company A Updated', $organization->name);
        $this->assertSame('Company A SHPK', $organization->legal_name);
        $this->assertSame(OrganizationLegalForm::Shpk, $organization->legal_form);
        $this->assertSame('601234567', $organization->fiscal_number);
        $this->assertSame('info@company-a.test', $organization->email);
        $this->assertSame('https://company-a.test', $organization->website);
        $this->assertTrue($organization->isProfileComplete());
        $this->assertNotNull($organization->logo_path);
        Storage::disk('local')->assertExists($organization->logo_path);

        $this->actingAsCompanyA($data['user'], $organization)
            ->get($this->workspaceRoute('settings.company.logo', $organization))
            ->assertOk();
    }

    public function test_hr_user_cannot_access_company_settings(): void
    {
        $data = $this->createCompanyA();
        $hr = User::factory()->create();
        $data['organization']->users()->attach($hr->id, [
            'role' => OrganizationRole::Hr->value,
            'joined_at' => now(),
        ]);

        $this->actingAs($hr)
            ->withSession(['current_organization_id' => $data['organization']->id])
            ->get($this->workspaceRoute('settings.company.edit', $data['organization']))
            ->assertForbidden();

        $this->actingAs($hr)
            ->withSession(['current_organization_id' => $data['organization']->id])
            ->put($this->workspaceRoute('settings.company.update', $data['organization']), ['name' => 'Hacked'])
            ->assertForbidden();
    }

    public function test_company_a_cannot_update_company_b_via_session(): void
    {
        $companyA = $this->createCompanyA();
        $companyB = $this->createCompanyB();

        $this->actingAsCompanyA($companyA['user'], $companyA['organization'])
            ->withSession(['current_organization_id' => $companyB['organization']->id])
            ->get($this->workspaceRoute('settings.company.edit', $companyB['organization']))
            ->assertForbidden();

        $this->actingAsCompanyA($companyA['user'], $companyA['organization'])
            ->withSession(['current_organization_id' => $companyB['organization']->id])
            ->put($this->workspaceRoute('settings.company.update', $companyB['organization']), [
                'name' => 'Hacked B',
                'country_code' => 'XK',
                'timezone' => 'Europe/Zurich',
                'currency' => 'EUR',
                'locale' => 'en',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('organizations', [
            'id' => $companyB['organization']->id,
            'name' => 'Company B LLC',
        ]);
    }

    public function test_admin_can_update_company_settings(): void
    {
        $data = $this->createCompanyA();
        $admin = User::factory()->create();
        $data['organization']->users()->attach($admin->id, [
            'role' => OrganizationRole::Admin->value,
            'joined_at' => now(),
        ]);

        $this->actingAs($admin)
            ->withSession(['current_organization_id' => $data['organization']->id])
            ->put($this->workspaceRoute('settings.company.update', $data['organization']), array_merge(
                $this->companySettingsPayload($data['organization']),
                ['name' => 'Admin Updated Name'],
            ))
            ->assertRedirect($this->workspaceRoute('settings.company.edit', $data['organization']));

        $this->assertDatabaseHas('organizations', [
            'id' => $data['organization']->id,
            'name' => 'Admin Updated Name',
        ]);
    }

    public function test_clearing_optional_legal_form_succeeds(): void
    {
        $data = $this->createCompanyA();
        $data['organization']->update(['legal_form' => OrganizationLegalForm::Shpk]);

        $this->actingAsCompanyA($data['user'], $data['organization'])
            ->put($this->workspaceRoute('settings.company.update', $data['organization']), array_merge(
                $this->companySettingsPayload($data['organization']),
                ['legal_form' => ''],
            ))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertNull($data['organization']->fresh()->legal_form);
    }

    public function test_owner_can_remove_logo(): void
    {
        Storage::fake('local');

        $data = $this->createCompanyA();
        $organization = $data['organization'];
        $path = 'organizations/'.$organization->id.'/branding/test.png';
        Storage::disk('local')->put($path, 'fake');
        $organization->update(['logo_path' => $path]);

        $this->actingAsCompanyA($data['user'], $organization)
            ->put($this->workspaceRoute('settings.company.update', $organization), array_merge(
                $this->companySettingsPayload($organization),
                ['remove_logo' => true],
            ))
            ->assertRedirect();

        $organization->refresh();
        $this->assertNull($organization->logo_path);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_owner_can_update_workspace_slug_and_operational_settings(): void
    {
        $data = $this->createCompanyA();
        $organization = $data['organization'];

        $this->actingAsCompanyA($data['user'], $organization)
            ->put($this->workspaceRoute('settings.company.update', $organization), array_merge(
                $this->companySettingsPayload($organization),
                [
                    'slug' => 'company-a-renamed',
                    'work_week_days' => ['mon', 'tue', 'wed', 'thu', 'fri'],
                    'observe_kosovo_holidays' => true,
                    'hr_can_invite' => false,
                ],
            ))
            ->assertRedirect($this->workspaceRoute('settings.company.edit', $organization->fresh()));

        $organization->refresh();
        $this->assertSame('company-a-renamed', $organization->slug);
        $this->assertFalse($organization->hr_can_invite);
    }

    public function test_hr_cannot_invite_when_disabled_in_company_settings(): void
    {
        $data = $this->createCompanyA();
        $data['organization']->update(['hr_can_invite' => false]);

        $hr = User::factory()->create();
        $data['organization']->users()->attach($hr->id, [
            'role' => OrganizationRole::Hr->value,
            'joined_at' => now(),
        ]);

        $this->actingAs($hr)
            ->withSession(['current_organization_id' => $data['organization']->id])
            ->post($this->workspaceRoute('team.invitations.store', $data['organization']), [
                'email' => 'new-hire@test.com',
                'role' => 'employee',
            ])
            ->assertForbidden();
    }
}
