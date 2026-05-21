<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Models\OrganizationContractTemplate;
use App\Models\User;
use App\Services\RegisterOrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationContractTemplateSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_contract_template_settings(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->get($this->workspaceRoute('settings.contract-templates.index', $result['organization']))
            ->assertOk()
            ->assertSee(__('documents.templates.settings.title'), false)
            ->assertSee(__('documents.templates.types.full_time.label'), false);
    }

    public function test_owner_can_create_custom_contract_template(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->post($this->workspaceRoute('settings.contract-templates.store', $result['organization']), [
                'name' => 'Consultant agreement',
                'description' => 'For external consultants',
                'body' => '<p>Agreement with :employee_name at :company_name.</p>',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('organization_contract_templates', [
            'organization_id' => $result['organization']->id,
            'name' => 'Consultant agreement',
            'slug' => 'consultant-agreement',
            'is_system' => false,
        ]);
    }

    public function test_owner_can_update_contract_template_body(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $template = OrganizationContractTemplate::query()
            ->where('slug', 'full_time')
            ->firstOrFail();

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->put($this->workspaceRoute('settings.contract-templates.update', $result['organization'], [
                'template' => $template->slug,
            ]), [
                'name' => $template->name,
                'description' => $template->description,
                'body' => '<p>Updated clause for :employee_name.</p>',
                'is_active' => '1',
            ])
            ->assertRedirect($this->workspaceRoute('settings.contract-templates.index', $result['organization']));

        $this->assertDatabaseHas('organization_contract_templates', [
            'id' => $template->id,
            'body' => '<p>Updated clause for :employee_name.</p>',
        ]);
    }

    public function test_hr_cannot_access_contract_template_settings(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $hr = User::factory()->create();
        $result['organization']->users()->attach($hr->id, [
            'role' => OrganizationRole::Hr->value,
            'joined_at' => now(),
        ]);

        $this->actingAs($hr)
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->get($this->workspaceRoute('settings.contract-templates.index', $result['organization']))
            ->assertForbidden();
    }

    public function test_owner_cannot_delete_system_template(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $template = OrganizationContractTemplate::query()
            ->where('slug', 'nda')
            ->firstOrFail();

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->delete($this->workspaceRoute('settings.contract-templates.destroy', $result['organization'], [
                'template' => $template->slug,
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('organization_contract_templates', [
            'id' => $template->id,
        ]);
    }
}
