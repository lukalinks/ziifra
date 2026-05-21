<?php

namespace Tests\Feature;

use App\Enums\EmployeeDocumentType;
use App\Mail\DocumentExpiringMail;
use App\Models\Employee;
use App\Services\RegisterOrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DocumentExpiryReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_expiry_reminder_command_sends_email(): void
    {
        Mail::fake();

        $result = app(RegisterOrganizationService::class)->register(
            'Owner',
            'owner@acme.test',
            'password123',
            'Acme SHPK',
        );

        $employee = Employee::factory()->forOrganization($result['organization'])->create();

        \App\Models\EmployeeDocument::query()->create([
            'organization_id' => $result['organization']->id,
            'employee_id' => $employee->id,
            'uploaded_by_user_id' => $result['user']->id,
            'type' => EmployeeDocumentType::IdDocument,
            'title' => 'ID Card',
            'file_path' => 'organizations/1/doc.pdf',
            'original_filename' => 'id.pdf',
            'expires_at' => now()->addDays(14),
        ]);

        Artisan::call('documents:send-expiry-reminders');

        Mail::assertQueued(DocumentExpiringMail::class);
    }
}
