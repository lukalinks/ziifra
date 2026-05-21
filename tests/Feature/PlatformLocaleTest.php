<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Services\DemoDataService;
use App\Services\LocaleConfigurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\UsesDemoOrganization;
use Tests\TestCase;

class PlatformLocaleTest extends TestCase
{
    use RefreshDatabase;
    use UsesDemoOrganization;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget('platform.languages');
        app(LocaleConfigurationService::class)->update(['en', 'sq', 'de'], 'en');
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function enabledLocales(): array
    {
        return [
            'english' => ['en'],
            'albanian' => ['sq'],
            'german' => ['de'],
        ];
    }

    #[DataProvider('enabledLocales')]
    public function test_owner_workspace_modules_load_in_each_locale(string $locale): void
    {
        $demo = $this->seedDemoOrganization();
        $organization = $demo['organization'];
        $demo['owner']->update(['locale' => $locale]);

        $routes = [
            'dashboard',
            'employees.index',
            'documents.index',
            'leave.index',
            'payroll.index',
            'invoices.index',
            'expenses.index',
            'projects.index',
            'time.index',
            'reports.index',
            'reports.export',
            'chat.index',
            'settings.index',
            'settings.company.edit',
        ];

        foreach ($routes as $routeName) {
            $this->actingAsOwner($demo)
                ->withSession(['locale' => $locale, 'current_organization_id' => $organization->id])
                ->get($this->workspaceRoute($routeName, $organization))
                ->assertOk("Failed {$routeName} for locale {$locale}");
        }
    }

    #[DataProvider('enabledLocales')]
    public function test_employee_self_service_modules_load_in_each_locale(string $locale): void
    {
        $demo = $this->seedDemoOrganization();
        $organization = $demo['organization'];
        $demo['employee_user']->update(['locale' => $locale]);

        foreach (['dashboard', 'leave.index', 'expenses.index', 'time.index', 'chat.index'] as $routeName) {
            $this->actingAsEmployee($demo)
                ->withSession(['locale' => $locale])
                ->get($this->workspaceRoute($routeName, $organization))
                ->assertOk("Failed {$routeName} for locale {$locale}");
        }
    }

    #[DataProvider('enabledLocales')]
    public function test_super_admin_panel_loads_in_each_locale(string $locale): void
    {
        $demo = app(DemoDataService::class)->seed();
        $admin = $demo['super_admin'];
        $admin->update(['locale' => $locale]);

        foreach ([
            'admin.dashboard',
            'admin.organizations.index',
            'admin.users.index',
            'admin.audit-logs.index',
            'admin.languages.edit',
        ] as $routeName) {
            $this->actingAs($admin)
                ->withSession(['locale' => $locale])
                ->get(route($routeName))
                ->assertOk("Failed {$routeName} for locale {$locale}");
        }
    }

    public function test_navigation_uses_albanian_labels_when_locale_is_sq(): void
    {
        $demo = $this->seedDemoOrganization();
        $demo['owner']->update(['locale' => 'sq']);

        $this->actingAsOwner($demo)
            ->withSession(['locale' => 'sq'])
            ->get($this->workspaceRoute('dashboard', $demo['organization']))
            ->assertOk()
            ->assertSee(__('navigation.dashboard', [], 'sq'), false)
            ->assertSee(__('navigation.employees', [], 'sq'), false);
    }

    public function test_guest_can_switch_locale_on_marketing_site(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('name="locale"', false);

        $this->post(route('locale.update'), ['locale' => 'de'])
            ->assertRedirect();

        $this->assertSame('de', session('locale'));

        $this->get(route('home'))
            ->assertOk();
    }

    public function test_disabled_locale_is_rejected(): void
    {
        app(LocaleConfigurationService::class)->update(['en', 'sq'], 'en');

        $demo = $this->seedDemoOrganization();

        $this->actingAsOwner($demo)
            ->post(route('locale.update'), ['locale' => 'de'])
            ->assertSessionHasErrors('locale');
    }

    public function test_disabling_locale_resets_organization_and_user_preferences(): void
    {
        $demo = $this->seedDemoOrganization();
        /** @var Organization $organization */
        $organization = $demo['organization'];
        /** @var User $owner */
        $owner = $demo['owner'];

        $organization->update(['locale' => 'de']);
        $owner->update(['locale' => 'de']);

        app(LocaleConfigurationService::class)->update(['en', 'sq'], 'sq');

        $this->assertSame('sq', $organization->fresh()->locale);
        $this->assertNull($owner->fresh()->locale);
    }

    public function test_owner_can_save_company_settings_with_each_enabled_locale(): void
    {
        $demo = $this->seedDemoOrganization();
        $organization = $demo['organization'];

        foreach (['en', 'sq', 'de'] as $locale) {
            $this->actingAsOwner($demo)
                ->put($this->workspaceRoute('settings.company.update', $organization), $this->companySettingsPayload($organization, [
                    'locale' => $locale,
                ]))
                ->assertRedirect();

            $this->assertSame($locale, $organization->fresh()->locale);
        }
    }

    public function test_login_page_renders_with_locale_switcher(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('id="locale-switcher"', false);
    }

    public function test_login_page_shows_albanian_when_locale_is_sq(): void
    {
        $this->withSession(['locale' => 'sq'])
            ->get(route('login'))
            ->assertOk()
            ->assertSee(__('auth_pages.login.heading', [], 'sq'), false)
            ->assertSee(__('auth_pages.login.submit', [], 'sq'), false);
    }

    public function test_company_settings_page_shows_albanian_when_locale_is_sq(): void
    {
        $demo = $this->seedDemoOrganization();
        $demo['owner']->update(['locale' => 'sq']);

        $this->actingAsOwner($demo)
            ->withSession(['locale' => 'sq', 'current_organization_id' => $demo['organization']->id])
            ->get($this->workspaceRoute('settings.company.edit', $demo['organization']))
            ->assertOk()
            ->assertSee(__('settings.company.title', [], 'sq'), false)
            ->assertSee(__('settings.company.identity', [], 'sq'), false);
    }
}
