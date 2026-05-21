<?php

namespace Database\Seeders;

use App\Services\DemoDataService;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $data = app(DemoDataService::class)->seed();

        $this->command?->info('Demo data seeded (Pro plan, full module sample data).');
        $this->command?->line('Super admin: '.$data['super_admin']->email.' / password → /admin');
        $this->command?->line('Owner: owner@demo.test / password → /o/'.$data['organization']->slug.'/dashboard');
        $this->command?->line('HR: hr@demo.test / password (linked employee profile, full HR access)');
        $this->command?->line('Manager: manager@demo.test / password');
        $this->command?->line('Employee: employee@demo.test / password (self-service: leave, expenses, time, chat)');
    }
}
