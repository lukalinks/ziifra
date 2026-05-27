<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_folders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'name']);
        });

        Schema::table('employee_documents', function (Blueprint $table): void {
            $table->foreignId('document_folder_id')
                ->nullable()
                ->after('uploaded_by_user_id')
                ->constrained('document_folders')
                ->nullOnDelete();

            $table->index(['organization_id', 'document_folder_id']);
        });
    }

    public function down(): void
    {
        Schema::table('employee_documents', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('document_folder_id');
        });

        Schema::dropIfExists('document_folders');
    }
};
