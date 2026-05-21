<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('stripe_id')->nullable()->after('suspended_at');
            $table->string('stripe_subscription_id')->nullable()->after('stripe_id');
            $table->string('stripe_subscription_status', 32)->nullable()->after('stripe_subscription_id');
            $table->timestamp('stripe_subscription_ends_at')->nullable()->after('stripe_subscription_status');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn([
                'stripe_id',
                'stripe_subscription_id',
                'stripe_subscription_status',
                'stripe_subscription_ends_at',
            ]);
        });
    }
};
