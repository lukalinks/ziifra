<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            $table->string('employee_code', 50)->nullable()->after('last_name');
            $table->index(['organization_id', 'employee_code']);
        });

        Schema::create('employee_hourly_rates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->decimal('hourly_rate', 10, 2);
            $table->string('currency', 3)->default('EUR');
            $table->timestamps();

            $table->unique(['employee_id', 'year', 'month']);
        });

        Schema::create('daily_hours_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->date('work_date');
            $table->decimal('hours', 5, 2)->default(0);
            $table->string('approval_status')->default('pending');
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'project_id', 'work_date']);
            $table->index(['project_id', 'work_date']);
            $table->index(['organization_id', 'approval_status']);
        });

        Schema::create('project_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('category');
            $table->string('title');
            $table->string('file_path');
            $table->string('original_filename');
            $table->timestamp('uploaded_at');
            $table->timestamps();

            $table->index(['project_id', 'category']);
        });

        Schema::create('workspace_nav_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('label');
            $table->string('url');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->foreignId('project_id')->nullable()->after('organization_id')->constrained()->nullOnDelete();
            $table->date('period_start')->nullable()->after('due_date');
            $table->date('period_end')->nullable()->after('period_start');
            $table->string('source')->default('manual')->after('status');
            $table->json('line_items')->nullable()->after('notes');
        });

        Schema::table('payroll_items', function (Blueprint $table): void {
            $table->decimal('hours_worked', 8, 2)->nullable()->after('employee_id');
            $table->decimal('hourly_rate', 10, 2)->nullable()->after('hours_worked');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_items', function (Blueprint $table): void {
            $table->dropColumn(['hours_worked', 'hourly_rate']);
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('project_id');
            $table->dropColumn(['period_start', 'period_end', 'source', 'line_items']);
        });

        Schema::dropIfExists('workspace_nav_items');
        Schema::dropIfExists('project_documents');
        Schema::dropIfExists('daily_hours_entries');
        Schema::dropIfExists('employee_hourly_rates');

        Schema::table('employees', function (Blueprint $table): void {
            $table->dropIndex(['organization_id', 'employee_code']);
            $table->dropColumn('employee_code');
        });
    }
};
