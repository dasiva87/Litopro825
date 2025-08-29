<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('social_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('requester_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('target_company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('target_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('connection_type');
            $table->string('status')->default('pending');
            $table->text('message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Índices para consultas frecuentes
            $table->index(['company_id', 'status']);
            $table->index(['target_company_id', 'status']);
            $table->index(['connection_type', 'status']);
            $table->index(['created_at']);

            // Una empresa no puede tener múltiples conexiones del mismo tipo con otra empresa
            $table->unique(['company_id', 'target_company_id', 'connection_type'], 'unique_company_connection');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_connections');
    }
};
