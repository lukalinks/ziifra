<?php

namespace Tests\Feature;

use App\Mail\WelcomeMail;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_create_organization(): void
    {
        Mail::fake();

        $response = $this->post('/register', [
            'name' => 'Arben Krasniqi',
            'email' => 'arben@acme.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'company_name' => 'Acme SHPK',
        ]);

        $this->assertDatabaseHas('users', ['email' => 'arben@acme.test']);
        $this->assertDatabaseHas('organizations', ['name' => 'Acme SHPK']);

        $user = User::query()->where('email', 'arben@acme.test')->first();
        $organization = Organization::query()->where('name', 'Acme SHPK')->first();

        $response->assertRedirect($this->workspaceRoute('dashboard', $organization));

        $this->assertTrue($user->belongsToOrganization($organization));
        $this->assertEquals('owner', $user->roleIn($organization)?->value);

        Mail::assertQueued(WelcomeMail::class, function (WelcomeMail $mail) use ($user, $organization) {
            return $mail->hasTo($user->email)
                && $mail->organization->is($organization);
        });
    }
}
