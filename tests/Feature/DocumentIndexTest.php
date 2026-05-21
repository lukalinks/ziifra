<?php

namespace Tests\Feature;

use App\Enums\EmployeeDocumentType;
use App\Enums\OrganizationRole;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\User;
use App\Services\RegisterOrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_documents_index_with_all_org_documents(): void
    {
        Storage::fake('local');

        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $employeeA = Employee::factory()->forOrganization($result['organization'])->create([
            'first_name' => 'Arben',
            'last_name' => 'Krasniqi',
        ]);
        $employeeB = Employee::factory()->forOrganization($result['organization'])->create([
            'first_name' => 'Era',
            'last_name' => 'Gashi',
        ]);

        EmployeeDocument::query()->create([
            'organization_id' => $result['organization']->id,
            'employee_id' => $employeeA->id,
            'uploaded_by_user_id' => $result['user']->id,
            'type' => EmployeeDocumentType::Contract,
            'title' => 'Contract A',
            'file_path' => 'organizations/1/employees/1/documents/a.pdf',
            'original_filename' => 'a.pdf',
        ]);

        EmployeeDocument::query()->create([
            'organization_id' => $result['organization']->id,
            'employee_id' => $employeeB->id,
            'uploaded_by_user_id' => $result['user']->id,
            'type' => EmployeeDocumentType::Certificate,
            'title' => 'Diploma B',
            'file_path' => 'organizations/1/employees/2/documents/b.pdf',
            'original_filename' => 'b.pdf',
        ]);

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->get($this->workspaceRoute('documents.index', $result['organization']))
            ->assertOk()
            ->assertSee('Contract A', false)
            ->assertSee('Diploma B', false)
            ->assertSee('Arben Krasniqi', false)
            ->assertSee('Era Gashi', false);
    }

    public function test_owner_can_filter_documents_by_employee(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $employeeA = Employee::factory()->forOrganization($result['organization'])->create();
        $employeeB = Employee::factory()->forOrganization($result['organization'])->create();

        EmployeeDocument::query()->create([
            'organization_id' => $result['organization']->id,
            'employee_id' => $employeeA->id,
            'uploaded_by_user_id' => $result['user']->id,
            'type' => EmployeeDocumentType::Other,
            'title' => 'Only A',
            'file_path' => 'organizations/1/employees/1/documents/a.pdf',
            'original_filename' => 'a.pdf',
        ]);

        EmployeeDocument::query()->create([
            'organization_id' => $result['organization']->id,
            'employee_id' => $employeeB->id,
            'uploaded_by_user_id' => $result['user']->id,
            'type' => EmployeeDocumentType::Other,
            'title' => 'Only B',
            'file_path' => 'organizations/1/employees/2/documents/b.pdf',
            'original_filename' => 'b.pdf',
        ]);

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->get($this->workspaceRoute('documents.index', $result['organization'], [
                'employee_id' => $employeeA->id,
            ]))
            ->assertOk()
            ->assertSee('Only A', false)
            ->assertDontSee('Only B', false);
    }

    public function test_owner_can_upload_document_from_index(): void
    {
        Storage::fake('local');

        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $employee = Employee::factory()->forOrganization($result['organization'])->create();

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->post($this->workspaceRoute('documents.store', $result['organization']), [
                'employee_id' => $employee->id,
                'type' => EmployeeDocumentType::Contract->value,
                'title' => 'From library',
                'file' => UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect($this->workspaceRoute('documents.index', $result['organization']));

        $this->assertDatabaseHas('employee_documents', [
            'employee_id' => $employee->id,
            'title' => 'From library',
        ]);
    }

    public function test_manager_can_view_but_not_upload_from_documents_index(): void
    {
        Storage::fake('local');

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

        EmployeeDocument::query()->create([
            'organization_id' => $result['organization']->id,
            'employee_id' => $employee->id,
            'uploaded_by_user_id' => $result['user']->id,
            'type' => EmployeeDocumentType::IdDocument,
            'title' => 'Passport scan',
            'file_path' => 'organizations/1/employees/1/documents/id.pdf',
            'original_filename' => 'id.pdf',
        ]);

        $this->actingAs($manager)
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->get($this->workspaceRoute('documents.index', $result['organization']))
            ->assertOk()
            ->assertSee('Passport scan', false)
            ->assertDontSee(__('documents.upload_from_index_hint'), false);

        $this->actingAs($manager)
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->post($this->workspaceRoute('documents.store', $result['organization']), [
                'employee_id' => $employee->id,
                'type' => EmployeeDocumentType::Other->value,
                'title' => 'Blocked',
                'file' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'),
            ])
            ->assertForbidden();
    }

    public function test_employee_cannot_access_documents_index(): void
    {
        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $employeeUser = User::factory()->create();
        $result['organization']->users()->attach($employeeUser->id, [
            'role' => OrganizationRole::Employee->value,
            'joined_at' => now(),
        ]);

        $this->actingAs($employeeUser)
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->get($this->workspaceRoute('documents.index', $result['organization']))
            ->assertForbidden();
    }

    public function test_delete_from_index_redirects_back_to_documents(): void
    {
        Storage::fake('local');

        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $employee = Employee::factory()->forOrganization($result['organization'])->create();
        $path = sprintf('organizations/%d/employees/%d/documents/file.pdf', $result['organization']->id, $employee->id);
        Storage::disk('local')->put($path, 'content');

        $document = EmployeeDocument::query()->create([
            'organization_id' => $result['organization']->id,
            'employee_id' => $employee->id,
            'uploaded_by_user_id' => $result['user']->id,
            'type' => EmployeeDocumentType::Other,
            'title' => 'Remove me',
            'file_path' => $path,
            'original_filename' => 'file.pdf',
        ]);

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->delete($this->workspaceRoute('employees.documents.destroy', $result['organization'], [
                'employee' => $employee,
                'document' => $document,
            ]), ['redirect' => 'documents'])
            ->assertRedirect($this->workspaceRoute('documents.index', $result['organization']));

        $this->assertDatabaseMissing('employee_documents', ['id' => $document->id]);
    }
}
