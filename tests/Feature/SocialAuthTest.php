<?php

namespace Tests\Feature;

use App\Enums\OAuthProvider;
use App\Models\Organization;
use App\Models\User;
use App\Mail\WelcomeMail;
use App\Services\RegisterOrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class SocialAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.google.client_id' => 'test-google-id',
            'services.google.client_secret' => 'test-google-secret',
        ]);
    }

    public function test_google_login_redirects_existing_user_to_dashboard(): void
    {
        $register = app(RegisterOrganizationService::class);
        $result = $register->register('Owner', 'oauth@acme.test', 'password123', 'Acme SHPK');
        $user = $result['user'];

        Socialite::fake('google', $this->socialiteUser());

        $this->withSession(['oauth_intent' => 'login'])
            ->get('/auth/google/callback')
            ->assertRedirect($this->workspaceRoute('dashboard', $result['organization']));

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('oauth_accounts', [
            'user_id' => $user->id,
            'provider' => OAuthProvider::Google->value,
            'provider_id' => 'google-123',
        ]);
    }

    public function test_google_register_redirects_to_complete_workspace(): void
    {
        Socialite::fake('google', $this->socialiteUser());

        $this->withSession(['oauth_intent' => 'register'])
            ->get('/auth/google/callback')
            ->assertRedirect(route('register.oauth.complete'));

        $this->assertGuest();
        $this->assertNotNull(session('oauth_pending'));
    }

    public function test_oauth_registration_completes_workspace(): void
    {
        Mail::fake();

        Socialite::fake('google', $this->socialiteUser());

        $response = $this->withSession([
            'oauth_intent' => 'register',
            'oauth_pending' => [
                'name' => 'OAuth User',
                'email' => 'oauth@acme.test',
                'provider' => OAuthProvider::Google->value,
                'provider_id' => 'google-123',
                'avatar' => null,
            ],
        ])->post('/register/oauth/complete', [
            'company_name' => 'OAuth SHPK',
        ]);

        $user = User::query()->where('email', 'oauth@acme.test')->first();
        $organization = Organization::query()->where('name', 'OAuth SHPK')->first();
        $this->assertNotNull($user);
        $this->assertNull($user->password);
        $this->assertDatabaseHas('organizations', ['name' => 'OAuth SHPK']);

        $response->assertRedirect($this->workspaceRoute('dashboard', $organization));

        Mail::assertQueued(WelcomeMail::class, fn (WelcomeMail $mail) => $mail->hasTo('oauth@acme.test'));
    }

    private function socialiteUser(): SocialiteUser
    {
        $user = new SocialiteUser;
        $user->id = 'google-123';
        $user->name = 'OAuth User';
        $user->email = 'oauth@acme.test';
        $user->avatar = null;

        return $user;
    }
}
