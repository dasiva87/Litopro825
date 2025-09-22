<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_integrations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive', 'testing', 'suspended'])->default('testing');

            // Configuración básica
            $table->string('integration_type'); // webhook_outbound, webhook_inbound, api_client, oauth_app
            $table->string('provider')->nullable(); // slack, zapier, custom, etc.
            $table->string('version')->default('1.0');

            // Configuración de autenticación
            $table->enum('auth_type', ['none', 'api_key', 'bearer_token', 'oauth2', 'basic_auth', 'signature'])->default('api_key');
            $table->json('auth_config')->nullable(); // Configuración específica de auth
            $table->timestamp('auth_expires_at')->nullable();

            // Configuración de endpoint
            $table->string('endpoint_url')->nullable(); // Para webhooks salientes
            $table->string('callback_url')->nullable(); // Para webhooks entrantes
            $table->json('headers')->nullable(); // Headers personalizados
            $table->enum('http_method', ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'])->default('POST');

            // Configuración de eventos
            $table->json('subscribed_events')->nullable(); // Eventos que disparan esta integración
            $table->json('event_filters')->nullable(); // Filtros adicionales por evento
            $table->boolean('transform_payload')->default(false); // Si debe transformar el payload
            $table->text('payload_template')->nullable(); // Template para el payload

            // Configuración de seguridad
            $table->string('webhook_secret')->nullable(); // Para validar webhooks entrantes
            $table->json('ip_whitelist')->nullable(); // IPs permitidas
            $table->boolean('verify_ssl')->default(true);
            $table->integer('timeout_seconds')->default(30);

            // Configuración de retry y rate limiting
            $table->json('retry_config')->nullable(); // Configuración de reintentos
            $table->json('rate_limits')->nullable(); // Límites de rate
            $table->boolean('enable_circuit_breaker')->default(true);
            $table->integer('circuit_breaker_threshold')->default(5); // Fallos consecutivos para abrir circuito

            // Configuración de logs y monitoreo
            $table->boolean('log_requests')->default(true);
            $table->boolean('log_responses')->default(true);
            $table->integer('log_retention_days')->default(30);
            $table->json('alerting_config')->nullable(); // Configuración de alertas

            // Métricas de rendimiento
            $table->bigInteger('total_requests')->default(0);
            $table->bigInteger('successful_requests')->default(0);
            $table->bigInteger('failed_requests')->default(0);
            $table->decimal('success_rate', 5, 2)->nullable();
            $table->integer('avg_response_time_ms')->nullable();

            // Estado del circuito y salud
            $table->enum('circuit_state', ['closed', 'open', 'half_open'])->default('closed');
            $table->timestamp('circuit_opened_at')->nullable();
            $table->integer('consecutive_failures')->default(0);
            $table->timestamp('last_success_at')->nullable();
            $table->timestamp('last_failure_at')->nullable();
            $table->text('last_error')->nullable();

            // Configuración de transformación de datos
            $table->json('field_mappings')->nullable(); // Mapeo de campos
            $table->text('request_transformer')->nullable(); // Código para transformar request
            $table->text('response_transformer')->nullable(); // Código para transformar response

            // Metadatos
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade'); // Para integraciones específicas de empresa
            $table->text('notes')->nullable();
            $table->json('tags')->nullable();

            $table->timestamps();

            // Índices
            $table->index(['integration_type', 'status']);
            $table->index(['status', 'created_at']);
            $table->index(['company_id', 'status']);
            $table->index(['provider', 'integration_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_integrations');
    }
};
