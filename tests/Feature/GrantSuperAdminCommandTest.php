<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class GrantSuperAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_creates_super_admin_with_create_flag(): void
    {
        $exit = Artisan::call('ziifra:grant-super-admin', [
            'email' => 'platform@ziifra.test',
            '--create' => true,
        ]);

        $this->assertSame(0, $exit);
        $user = User::query()->where('email', 'platform@ziifra.test')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->isSuperAdmin());
    }

    public function test_command_grants_existing_user(): void
    {
        $user = User::factory()->create(['email' => 'existing@ziifra.test']);

        Artisan::call('ziifra:grant-super-admin', [
            'email' => 'existing@ziifra.test',
        ]);

        $this->assertTrue($user->fresh()->isSuperAdmin());
    }
}
