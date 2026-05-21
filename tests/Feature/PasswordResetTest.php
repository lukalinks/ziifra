<?php

namespace Tests\Feature;

use App\Mail\ResetPasswordMail;
use App\Models\User;
use App\Services\RegisterOrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_shows_forgot_password_link(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Forgot password?')
            ->assertSee(route('password.request'), false);
    }

    public function test_user_can_request_password_reset_link(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $response = $this->post('/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertRedirect()
            ->assertSessionHas('status');

        Mail::assertQueued(ResetPasswordMail::class, function (ResetPasswordMail $mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_unknown_email_still_shows_success_message(): void
    {
        Mail::fake();

        $response = $this->post('/forgot-password', [
            'email' => 'nobody@example.test',
        ]);

        $response->assertRedirect()
            ->assertSessionHas('status', __('passwords.sent'))
            ->assertSessionDoesntHaveErrors('email');

        Mail::assertNothingSent();
    }

    public function test_reset_password_page_renders_with_token_and_email(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->get('/reset-password/'.$token.'?email='.urlencode($user->email))
            ->assertOk()
            ->assertSee('Reset password', false)
            ->assertSee($user->email, false)
            ->assertSee('value="'.$token.'"', false);
    }

    public function test_user_can_reset_password_with_valid_token(): void
    {
        $user = User::factory()->create([
            'password' => 'old-password-123',
        ]);

        $token = Password::createToken($user);

        $response = $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password-456',
            'password_confirmation' => 'new-password-456',
        ]);

        $response->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $user->refresh();

        $this->assertTrue(Hash::check('new-password-456', $user->password));
    }

    public function test_oauth_user_without_password_can_set_one_via_reset(): void
    {
        $register = app(RegisterOrganizationService::class);
        $result = $register->register('OAuth User', 'oauth-reset@acme.test', 'temp-password', 'Acme SHPK');
        $user = $result['user'];
        $user->forceFill(['password' => null])->save();

        $token = Password::createToken($user);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password-789',
            'password_confirmation' => 'new-password-789',
        ])->assertRedirect(route('login'));

        $user->refresh();

        $this->assertTrue($user->hasPassword());
        $this->assertTrue(Hash::check('new-password-789', $user->password));

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'new-password-789',
        ])->assertRedirect($this->workspaceRoute('dashboard', $result['organization']));
    }

    public function test_reset_email_contains_valid_reset_link(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Mail::assertQueued(ResetPasswordMail::class, function (ResetPasswordMail $mail) use ($user) {
            $this->assertStringContainsString('/reset-password/', $mail->resetUrl);
            $this->assertStringContainsString('email='.urlencode($user->email), $mail->resetUrl);

            return $mail->hasTo($user->email);
        });
    }
}
