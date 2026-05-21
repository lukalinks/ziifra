<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class GrantSuperAdminCommand extends Command
{
    protected $signature = 'ziifra:grant-super-admin
                            {email? : The user email address (defaults to SUPER_ADMIN_EMAIL)}
                            {--create : Create the user if they do not exist yet}';

    protected $description = 'Grant platform super admin access to a user by email';

    public function handle(): int
    {
        $email = strtolower(trim($this->argument('email') ?? config('admin.default_super_admin_email', '')));

        if ($email === '') {
            $this->error('Provide an email or set SUPER_ADMIN_EMAIL in .env.');

            return self::FAILURE;
        }

        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            if (! $this->option('create')) {
                $this->error("No user found with email [{$email}]. Run with --create to register them.");

                return self::FAILURE;
            }

            $user = User::query()->create([
                'name' => config('admin.default_super_admin_name', 'ZIIFRA Admin'),
                'email' => $email,
                'password' => Hash::make(config('admin.default_super_admin_password', 'password')),
                'email_verified_at' => now(),
                'is_super_admin' => true,
            ]);

            $this->info("Created super admin {$user->email} (id {$user->id}).");

            return self::SUCCESS;
        }

        if ($user->isSuperAdmin()) {
            $this->info("{$user->email} is already a super admin.");

            return self::SUCCESS;
        }

        $user->update(['is_super_admin' => true]);

        $this->info("Super admin granted to {$user->email}.");
        $this->line('They can open /admin after logging in.');

        return self::SUCCESS;
    }
}
