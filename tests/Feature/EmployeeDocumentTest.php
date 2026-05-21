<?php

namespace Tests\Feature;

use App\Enums\EmployeeDocumentType;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Services\RegisterOrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmployeeDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_upload_and_download_document(): void
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
            ->post($this->workspaceRoute('employees.documents.store', $result['organization'], ['employee' => $employee]), [
                'type' => EmployeeDocumentType::Contract->value,
                'title' => 'Employment contract 2026',
                'file' => UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf'),
                'expires_at' => now()->addYear()->toDateString(),
            ])
            ->assertRedirect($this->workspaceRoute('employees.show', $result['organization'], ['employee' => $employee]));

        $document = EmployeeDocument::query()->where('employee_id', $employee->id)->first();
        $this->assertNotNull($document);
        $this->assertSame('Employment contract 2026', $document->title);
        Storage::disk('local')->assertExists($document->file_path);

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->get($this->workspaceRoute('employees.documents.download', $result['organization'], [
                'employee' => $employee,
                'document' => $document,
            ]))
            ->assertOk();
    }

    public function test_manager_can_view_but_not_upload_documents(): void
    {
        Storage::fake('local');

        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $managerUser = \App\Models\User::factory()->create();
        $employee = Employee::factory()->forOrganization($result['organization'])->create();

        $result['organization']->users()->attach($managerUser->id, [
            'role' => \App\Enums\OrganizationRole::Manager->value,
            'joined_at' => now(),
        ]);

        $document = EmployeeDocument::query()->create([
            'organization_id' => $result['organization']->id,
            'employee_id' => $employee->id,
            'uploaded_by_user_id' => $result['user']->id,
            'type' => EmployeeDocumentType::IdDocument,
            'title' => 'Passport',
            'file_path' => 'organizations/1/employees/1/documents/test.pdf',
            'original_filename' => 'passport.pdf',
        ]);

        Storage::disk('local')->put($document->file_path, 'pdf-content');

        $this->actingAs($managerUser)
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->get($this->workspaceRoute('employees.show', $result['organization'], ['employee' => $employee]))
            ->assertOk()
            ->assertSee('Passport', false);

        $this->actingAs($managerUser)
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->post($this->workspaceRoute('employees.documents.store', $result['organization'], ['employee' => $employee]), [
                'type' => EmployeeDocumentType::Other->value,
                'title' => 'Should fail',
                'file' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'),
            ])
            ->assertForbidden();
    }

    public function test_owner_can_delete_document(): void
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
            'title' => 'Old file',
            'file_path' => $path,
            'original_filename' => 'file.pdf',
        ]);

        $this->actingAs($result['user'])
            ->withSession(['current_organization_id' => $result['organization']->id])
            ->delete($this->workspaceRoute('employees.documents.destroy', $result['organization'], [
                'employee' => $employee,
                'document' => $document,
            ]))
            ->assertRedirect($this->workspaceRoute('employees.show', $result['organization'], ['employee' => $employee]));

        $this->assertDatabaseMissing('employee_documents', ['id' => $document->id]);
        Storage::disk('local')->assertMissing($path);
    }
}
