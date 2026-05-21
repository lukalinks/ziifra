<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('legal_name')->nullable()->after('name');
            $table->string('legal_form', 32)->nullable()->after('legal_name');
            $table->string('registration_number', 64)->nullable()->after('legal_form');
            $table->string('fiscal_number', 64)->nullable()->after('registration_number');
            $table->string('vat_number', 64)->nullable()->after('fiscal_number');

            $table->string('address_line_1')->nullable()->after('country_code');
            $table->string('address_line_2')->nullable()->after('address_line_1');
            $table->string('city')->nullable()->after('address_line_2');
            $table->string('postal_code', 32)->nullable()->after('city');

            $table->string('phone', 32)->nullable()->after('postal_code');
            $table->string('email')->nullable()->after('phone');
            $table->string('website')->nullable()->after('email');

            $table->string('timezone', 64)->default('Europe/Belgrade')->after('website');
            $table->char('currency', 3)->default('EUR')->after('timezone');
            $table->string('locale', 5)->default('en')->after('currency');

            $table->string('logo_path')->nullable()->after('locale');
            $table->string('primary_color', 7)->nullable()->after('logo_path');
            $table->string('accent_color', 7)->nullable()->after('primary_color');
            $table->string('brand_tagline')->nullable()->after('accent_color');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn([
                'legal_name',
                'legal_form',
                'registration_number',
                'fiscal_number',
                'vat_number',
                'address_line_1',
                'address_line_2',
                'city',
                'postal_code',
                'phone',
                'email',
                'website',
                'timezone',
                'currency',
                'locale',
                'logo_path',
                'primary_color',
                'accent_color',
                'brand_tagline',
            ]);
        });
    }
};
