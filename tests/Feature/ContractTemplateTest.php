<?php

namespace Tests\Feature;

use App\Enums\ContractTemplate;
use App\Enums\EmployeeDocumentType;
use App\Enums\OrganizationRole;
use App\Models\Employee;
use App\Models\OrganizationContractTemplate;
use App\Models\User;
use App\Services\RegisterOrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_download_blank_contract_template(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->get($this->workspaceRoute('documents.templates.download', $result['organization'], [
                'template' => ContractTemplate::FullTime->value,
            ]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_owner_can_generate_contract_pdf_for_employee(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $employee = Employee::factory()->forOrganization($result['organization'])->create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'start_date' => '2026-01-15',
            'gross_salary' => 1200,
        ]);

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->post($this->workspaceRoute('documents.templates.generate', $result['organization'], [
                'template' => ContractTemplate::FullTime->value,
            ]), [
                'employee_id' => $employee->id,
            ])
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_owner_can_save_generated_contract_to_employee_documents(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $employee = Employee::factory()->forOrganization($result['organization'])->create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
        ]);

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->post($this->workspaceRoute('documents.templates.generate', $result['organization'], [
                'template' => ContractTemplate::Nda->value,
            ]), [
                'employee_id' => $employee->id,
                'save_to_documents' => '1',
            ])
            ->assertRedirect($this->workspaceRoute('documents.index', $result['organization']));

        $this->assertDatabaseHas('employee_documents', [
            'employee_id' => $employee->id,
            'type' => EmployeeDocumentType::Contract->value,
        ]);
    }

    public function test_documents_index_shows_contract_templates(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $fullTime = OrganizationContractTemplate::query()
            ->where('slug', ContractTemplate::FullTime->value)
            ->firstOrFail();

        $nda = OrganizationContractTemplate::query()
            ->where('slug', ContractTemplate::Nda->value)
            ->firstOrFail();

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->get($this->workspaceRoute('documents.index', $result['organization']))
            ->assertOk()
            ->assertSee(__('documents.templates.title'), false)
            ->assertSee($fullTime->name, false)
            ->assertSee($nda->name, false);
    }

    public function test_manager_can_download_blank_but_not_generate_contract(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $manager = User::factory()->create();
        $result['organization']->users()->attach($manager->id, [
            'role' => OrganizationRole::Manager->value,
            'joined_at' => now(),
        ]);

        $employee = Employee::factory()->forOrganization($result['organization'])->create();

        $this->actingAs($manager)
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->get($this->workspaceRoute('documents.templates.download', $result['organization'], [
                'template' => ContractTemplate::Internship->value,
            ]))
            ->assertOk();

        $this->actingAs($manager)
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->post($this->workspaceRoute('documents.templates.generate', $result['organization'], [
                'template' => ContractTemplate::Internship->value,
            ]), [
                'employee_id' => $employee->id,
            ])
            ->assertForbidden();
    }
}
