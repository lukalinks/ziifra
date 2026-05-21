<?php

namespace Tests\Feature;

use App\Models\PlatformSetting;
use App\Models\User;
use App\Services\DemoDataService;
use App\Services\LocaleConfigurationService;
use App\Services\RegisterOrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Concerns\UsesDemoOrganization;
use Tests\TestCase;

class LanguageSettingsTest extends TestCase
{
    use RefreshDatabase;
    use UsesDemoOrganization;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget('platform.languages');
    }

    public function test_super_admin_can_configure_enabled_and_default_languages(): void
    {
        $demo = app(DemoDataService::class)->seed();
        $admin = $demo['super_admin'];

        $this->actingAs($admin)
            ->get(route('admin.languages.edit'))
            ->assertOk()
            ->assertSee('Shqip', false)
            ->assertSee('Deutsch', false);

        $this->actingAs($admin)
            ->put(route('admin.languages.update'), [
                'enabled' => ['en', 'sq', 'de'],
                'default' => 'sq',
            ])
            ->assertRedirect(route('admin.languages.edit'))
            ->assertSessionHas('status');

        $locales = app(LocaleConfigurationService::class);

        $this->assertSame(['en', 'sq', 'de'], $locales->enabledCodes());
        $this->assertSame('sq', $locales->defaultCode());

        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'platform.languages_updated',
        ]);
    }

    public function test_disabling_language_removes_it_from_enabled_options(): void
    {
        $locales = app(LocaleConfigurationService::class);
        $locales->update(['en', 'sq'], 'en');

        $options = $locales->enabledOptions();

        $this->assertArrayHasKey('en', $options);
        $this->assertArrayHasKey('sq', $options);
        $this->assertArrayNotHasKey('de', $options);
    }

    public function test_authenticated_user_can_switch_locale(): void
    {
        $demo = $this->seedDemoOrganization();
        $employeeUser = $demo['employee_user'];

        $this->actingAsEmployee($demo)
            ->post(route('locale.update'), ['locale' => 'sq'])
            ->assertRedirect();

        $this->assertSame('sq', $employeeUser->fresh()->locale);
        $this->assertSame('sq', session('locale'));
    }

    public function test_new_organization_uses_platform_default_locale(): void
    {
        app(LocaleConfigurationService::class)->update(['en', 'de'], 'de');

        $result = app(RegisterOrganizationService::class)->register(
            'Test Owner',
            'new-org@example.test',
            'password',
            'New Org GmbH',
        );

        $this->assertSame('de', $result['organization']->locale);
    }

    public function test_disabling_locale_resets_stored_organization_locale(): void
    {
        $demo = $this->seedDemoOrganization();
        $organization = $demo['organization'];
        $organization->update(['locale' => 'de']);

        app(LocaleConfigurationService::class)->update(['en', 'sq'], 'sq');

        $this->assertSame('sq', $organization->fresh()->locale);
    }

    public function test_non_super_admin_cannot_access_language_settings(): void
    {
        $demo = $this->seedDemoOrganization();

        $this->actingAsOwner($demo)
            ->get(route('admin.languages.edit'))
            ->assertForbidden();
    }
}
