<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('employees')
            ->select(['id', 'organization_id'])
            ->whereNull('employee_code')
            ->orderBy('id')
            ->lazyById()
            ->each(function (object $row): void {
                $code = \App\Models\Employee::generateUniqueCode((int) $row->organization_id);

                DB::table('employees')
                    ->where('id', $row->id)
                    ->update(['employee_code' => $code]);
            });
    }

    public function down(): void
    {
        //
    }
};
