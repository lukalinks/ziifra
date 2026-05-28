<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            $table->string('compensation_type', 20)->nullable()->after('gross_salary');
            $table->decimal('fixed_hourly_rate', 10, 2)->nullable()->after('compensation_type');
            $table->string('fixed_hourly_currency', 3)->nullable()->after('fixed_hourly_rate');
            $table->decimal('fixed_monthly_salary', 12, 2)->nullable()->after('fixed_hourly_currency');
            $table->string('fixed_salary_currency', 3)->nullable()->after('fixed_monthly_salary');
        });

        Schema::table('organizations', function (Blueprint $table): void {
            $table->json('payroll_settings')->nullable()->after('payslip_template');
            $table->json('invoice_settings')->nullable()->after('payroll_settings');
            $table->json('chat_settings')->nullable()->after('invoice_settings');
        });

        DB::table('organizations')
            ->where('timezone', 'Europe/Belgrade')
            ->update(['timezone' => 'Europe/Zurich']);

        foreach ([
            'sha' => 'shpk',
            'individual' => 'bi',
            'ngo' => 'branch',
            'other' => 'partnership',
        ] as $from => $to) {
            DB::table('organizations')->where('legal_form', $from)->update(['legal_form' => $to]);
        }

        DB::table('organizations')->whereNull('payroll_settings')->update([
            'payroll_settings' => json_encode([
                'trust_employee_percent' => 5,
                'trust_employer_percent' => 5,
                'vat_percent' => 8,
                'show_logo' => true,
                'show_vat' => true,
            ]),
        ]);
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table): void {
            $table->dropColumn(['payroll_settings', 'invoice_settings', 'chat_settings']);
        });

        Schema::table('employees', function (Blueprint $table): void {
            $table->dropColumn([
                'compensation_type',
                'fixed_hourly_rate',
                'fixed_hourly_currency',
                'fixed_monthly_salary',
                'fixed_salary_currency',
            ]);
        });

        DB::table('organizations')
            ->where('timezone', 'Europe/Zurich')
            ->update(['timezone' => 'Europe/Belgrade']);
    }
};
