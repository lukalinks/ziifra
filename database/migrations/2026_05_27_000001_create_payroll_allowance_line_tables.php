<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_allowances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->decimal('amount', 12, 2);
            $table->string('tax_treatment');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['organization_id', 'employee_id']);
        });

        Schema::create('payroll_item_allowances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payroll_item_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->decimal('amount', 12, 2);
            $table->string('tax_treatment');
            $table->string('kind');
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('payroll_item_id');
        });

        Schema::table('payroll_items', function (Blueprint $table): void {
            $table->decimal('exempt_allowances_total', 12, 2)->default(0)->after('allowances');
        });

        $now = now();

        $items = DB::table('payroll_items')->where('allowances', '>', 0)->get();

        foreach ($items as $row) {
            DB::table('payroll_item_allowances')->insert([
                'payroll_item_id' => $row->id,
                'label' => 'Allowances',
                'amount' => $row->allowances,
                'tax_treatment' => 'taxable',
                'kind' => 'recurring',
                'notes' => null,
                'sort_order' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('payroll_items', function (Blueprint $table): void {
            $table->dropColumn('exempt_allowances_total');
        });

        Schema::dropIfExists('payroll_item_allowances');
        Schema::dropIfExists('employee_allowances');
    }
};
