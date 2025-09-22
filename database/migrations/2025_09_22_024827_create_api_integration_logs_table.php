<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_integration_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_integration_id')->constrained('api_integrations')->onDelete('cascade');

            // Información de la solicitud
            $table->enum('direction', ['outbound', 'inbound']); // Saliente o entrante
            $table->string('event_type')->nullable(); // Tipo de evento que disparó la integración
            $table->string('event_id')->nullable(); // ID del objeto que disparó el evento

            // Request information
            $table->string('request_method')->nullable();
            $table->text('request_url')->nullable();
            $table->json('request_headers')->nullable();
            $table->longText('request_body')->nullable();
            $table->integer('request_size_bytes')->nullable();

            // Response information
            $table->integer('response_status')->nullable();
            $table->json('response_headers')->nullable();
            $table->longText('response_body')->nullable();
            $table->integer('response_size_bytes')->nullable();

            // Timing information
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->integer('timeout_ms')->nullable();

            // Status and error information
            $table->enum('status', ['pending', 'success', 'failed', 'timeout', 'cancelled'])->default('pending');
            $table->text('error_message')->nullable();
            $table->json('error_details')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('retry_at')->nullable();

            // Authentication information
            $table->string('auth_type_used')->nullable();
            $table->boolean('auth_success')->nullable();

            // IP and User Agent (for inbound requests)
            $table->string('client_ip')->nullable();
            $table->string('user_agent')->nullable();

            // Processing information
            $table->boolean('transformed')->default(false);
            $table->json('transformation_log')->nullable();
            $table->boolean('filtered')->default(false);
            $table->json('filter_results')->nullable();

            // Circuit breaker information
            $table->string('circuit_state')->nullable(); // closed, open, half_open
            $table->boolean('circuit_breaker_triggered')->default(false);

            // Correlation and tracing
            $table->string('correlation_id')->nullable(); // Para rastrear solicitudes relacionadas
            $table->string('trace_id')->nullable(); // Para distributed tracing
            $table->string('parent_span_id')->nullable();

            // Metadata
            $table->json('metadata')->nullable(); // Información adicional específica de la integración
            $table->text('notes')->nullable();

            $table->timestamps();

            // Índices
            $table->index(['api_integration_id', 'status']);
            $table->index(['status', 'started_at']);
            $table->index(['event_type', 'created_at']);
            $table->index(['direction', 'status']);
            $table->index(['correlation_id']);
            $table->index(['response_status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_integration_logs');
    }
};
