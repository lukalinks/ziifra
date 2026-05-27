<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->string('project_code', 32)->nullable()->after('name');
            $table->unique(['organization_id', 'project_code']);
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->dropUnique(['organization_id', 'project_code']);
            $table->dropColumn('project_code');
        });
    }
};
