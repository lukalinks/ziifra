<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('projects')
            ->select(['id', 'organization_id'])
            ->whereNull('project_code')
            ->orderBy('id')
            ->lazyById()
            ->each(function (object $row): void {
                $code = \App\Models\Project::generateUniqueCode((int) $row->organization_id);

                DB::table('projects')
                    ->where('id', $row->id)
                    ->update(['project_code' => $code]);
            });
    }

    public function down(): void
    {
        //
    }
};
