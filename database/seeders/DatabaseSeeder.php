<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (app()->environment('local')) {
            Artisan::call('ziifra:grant-super-admin', ['--create' => true]);
            $this->command?->info(trim(Artisan::output()));

            $this->call(DemoDataSeeder::class);
        }
    }
}
