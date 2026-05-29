<?php

namespace Tests\Feature;

use App\Services\OrganizationMailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\CreatesTwoOrganizations;
use Tests\TestCase;

class MailSettingsTest extends TestCase
{
    use CreatesTwoOrganizations;
    use RefreshDatabase;

    public function test_owner_can_view_and_save_mail_settings(): void
    {
        $data = $this->createCompanyA();
        $organization = $data['organization'];

        $this->actingAsCompanyA($data['user'], $organization)
            ->get($this->workspaceRoute('settings.mail.edit', $organization))
            ->assertOk()
            ->assertSee(__('settings.mail.use_custom_smtp'), false);

        $this->actingAsCompanyA($data['user'], $organization)
            ->put($this->workspaceRoute('settings.mail.update', $organization), [
                'mail_settings' => [
                    'enabled' => '1',
                    'host' => 'smtp.example.com',
                    'port' => '587',
                    'encryption' => 'tls',
                    'username' => 'mailer@example.com',
                    'password' => 'secret-pass',
                    'from_address' => 'noreply@example.com',
                    'from_name' => 'Example Corp',
                ],
            ])
            ->assertRedirect($this->workspaceRoute('settings.mail.edit', $organization))
            ->assertSessionHas('status');

        $organization->refresh();
        $settings = $organization->resolvedMailSettings();

        $this->assertTrue($settings['enabled']);
        $this->assertSame('smtp.example.com', $settings['host']);
        $this->assertSame('noreply@example.com', $settings['from_address']);
        $this->assertNotNull($settings['password']);
        $this->assertSame('secret-pass', Crypt::decryptString($settings['password']));
    }

    public function test_custom_smtp_registers_dynamic_mailer(): void
    {
        Mail::fake();

        $data = $this->createCompanyA();
        $organization = $data['organization'];

        $organization->update([
            'mail_settings' => [
                'enabled' => true,
                'host' => 'smtp.test.local',
                'port' => 587,
                'encryption' => 'tls',
                'username' => 'user',
                'password' => Crypt::encryptString('pass'),
                'from_address' => 'hr@test.local',
                'from_name' => 'Test Co',
            ],
        ]);

        $mail = app(OrganizationMailService::class);
        $mailerName = $mail->registerMailer($organization->fresh());

        $this->assertSame('org_mail_'.$organization->id, $mailerName);
        $this->assertSame('smtp.test.local', config('mail.mailers.'.$mailerName.'.host'));
    }

    public function test_incomplete_smtp_falls_back_to_platform_mailer(): void
    {
        $data = $this->createCompanyA();
        $organization = $data['organization'];

        $organization->update([
            'mail_settings' => [
                'enabled' => true,
                'host' => 'smtp.example.com',
                'port' => 587,
                'encryption' => 'tls',
                'username' => 'user@example.com',
                'password' => null,
                'from_address' => 'noreply@example.com',
                'from_name' => 'Example',
            ],
        ]);

        $mail = app(OrganizationMailService::class);

        $this->assertSame(OrganizationMailService::STATUS_INCOMPLETE, $mail->status($organization->fresh()));
        $this->assertSame(config('mail.default', 'smtp'), $mail->registerMailer($organization->fresh()));
    }

    public function test_status_is_active_when_fully_configured(): void
    {
        $data = $this->createCompanyA();
        $organization = $data['organization'];

        $organization->update([
            'mail_settings' => [
                'enabled' => true,
                'host' => 'smtp.example.com',
                'port' => 587,
                'encryption' => 'tls',
                'username' => '',
                'password' => null,
                'from_address' => 'hr@example.com',
                'from_name' => 'Example',
            ],
        ]);

        $this->assertSame(
            OrganizationMailService::STATUS_ACTIVE,
            app(OrganizationMailService::class)->status($organization->fresh()),
        );
    }

    public function test_hr_user_cannot_access_mail_settings(): void
    {
        $data = $this->createCompanyA();
        $hr = \App\Models\User::factory()->create();
        $data['organization']->users()->attach($hr->id, [
            'role' => \App\Enums\OrganizationRole::Hr->value,
            'joined_at' => now(),
        ]);

        $this->actingAs($hr)
            ->withSession(['current_organization_id' => $data['organization']->id])
            ->get($this->workspaceRoute('settings.mail.edit', $data['organization']))
            ->assertForbidden();
    }
}
