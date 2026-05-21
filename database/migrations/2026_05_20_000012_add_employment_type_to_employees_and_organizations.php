<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('employment_type', 32)->default('full_time')->after('manager_id');
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('default_employment_status');
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->string('default_employment_type', 32)->default('full_time')->after('observe_kosovo_holidays');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('employment_type');
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('default_employment_type');
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->string('default_employment_status', 32)->default('active')->after('observe_kosovo_holidays');
        });
    }
};
