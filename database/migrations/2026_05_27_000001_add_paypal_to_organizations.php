<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('paypal_subscription_id')->nullable()->after('stripe_subscription_ends_at');
            $table->string('paypal_subscription_status', 32)->nullable()->after('paypal_subscription_id');
            $table->timestamp('paypal_subscription_ends_at')->nullable()->after('paypal_subscription_status');
            $table->string('billing_payment_provider', 16)->nullable()->after('paypal_subscription_ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn([
                'paypal_subscription_id',
                'paypal_subscription_status',
                'paypal_subscription_ends_at',
                'billing_payment_provider',
            ]);
        });
    }
};
