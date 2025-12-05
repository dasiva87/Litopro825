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
        Schema::create('commercial_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('requester_company_id'); // Empresa que solicita
            $table->unsignedBigInteger('target_company_id');    // Empresa objetivo
            $table->unsignedBigInteger('requested_by_user_id'); // Usuario que solicita
            $table->enum('relationship_type', ['client', 'supplier']); // Tipo de relación solicitada
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('message')->nullable();
            $table->text('response_message')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->unsignedBigInteger('responded_by_user_id')->nullable();
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('requester_company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('target_company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('requested_by_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('responded_by_user_id')->references('id')->on('users')->onDelete('cascade');
            
            // Índices
            $table->index(['target_company_id', 'status'], 'commercial_requests_target_status');
            $table->index(['requester_company_id', 'status'], 'commercial_requests_requester_status');
            $table->index(['relationship_type', 'status'], 'commercial_requests_type_status');
            
            // Evitar solicitudes duplicadas
            $table->unique(['requester_company_id', 'target_company_id', 'relationship_type'], 'unique_commercial_request');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commercial_requests');
    }
};