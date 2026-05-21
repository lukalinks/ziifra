<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('plan', 32)->default('trial')->after('owner_id');
            $table->timestamp('trial_ends_at')->nullable()->after('plan');
            $table->timestamp('suspended_at')->nullable()->after('trial_ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['plan', 'trial_ends_at', 'suspended_at']);
        });
    }
};
