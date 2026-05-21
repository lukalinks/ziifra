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
            $table->decimal('monthly_allowances', 12, 2)->default(0)->after('gross_salary');
        });

        Schema::table('payroll_items', function (Blueprint $table): void {
            $table->decimal('base_gross_salary', 12, 2)->default(0)->after('employee_id');
            $table->decimal('allowances', 12, 2)->default(0)->after('base_gross_salary');
        });

        DB::table('payroll_items')->update([
            'base_gross_salary' => DB::raw('gross_salary'),
            'allowances' => 0,
        ]);
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            $table->dropColumn('monthly_allowances');
        });

        Schema::table('payroll_items', function (Blueprint $table): void {
            $table->dropColumn(['base_gross_salary', 'allowances']);
        });
    }
};
