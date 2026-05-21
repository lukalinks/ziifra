<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('hr_email')->nullable()->after('email');
            $table->string('reply_to_email')->nullable()->after('hr_email');
            $table->string('signatory_name')->nullable()->after('reply_to_email');
            $table->string('signatory_title')->nullable()->after('signatory_name');

            $table->json('work_week_days')->nullable()->after('locale');
            $table->unsignedTinyInteger('fiscal_year_start_month')->default(1)->after('work_week_days');
            $table->string('date_format', 16)->default('d/m/Y')->after('fiscal_year_start_month');
            $table->boolean('observe_kosovo_holidays')->default(true)->after('date_format');

            $table->string('default_employment_status', 32)->default('active')->after('observe_kosovo_holidays');
            $table->unsignedSmallInteger('probation_days')->nullable()->after('default_employment_status');
            $table->string('employee_id_prefix', 16)->nullable()->after('probation_days');
            $table->string('handbook_url')->nullable()->after('employee_id_prefix');

            $table->boolean('vat_registered')->default(false)->after('vat_number');
            $table->string('bank_name')->nullable()->after('handbook_url');
            $table->string('bank_iban', 34)->nullable()->after('bank_name');

            $table->boolean('hr_can_invite')->default(true)->after('bank_iban');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn([
                'hr_email',
                'reply_to_email',
                'signatory_name',
                'signatory_title',
                'work_week_days',
                'fiscal_year_start_month',
                'date_format',
                'observe_kosovo_holidays',
                'default_employment_status',
                'probation_days',
                'employee_id_prefix',
                'handbook_url',
                'vat_registered',
                'bank_name',
                'bank_iban',
                'hr_can_invite',
            ]);
        });
    }
};
