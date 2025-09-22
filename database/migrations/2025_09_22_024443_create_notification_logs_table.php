<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_rule_id')->nullable()->constrained('notification_rules')->onDelete('set null');
            $table->foreignId('notification_channel_id')->nullable()->constrained('notification_channels')->onDelete('set null');

            // Información del evento que disparó la notificación
            $table->string('event_type');
            $table->string('event_id')->nullable(); // ID del objeto que disparó el evento
            $table->json('event_data')->nullable(); // Datos del evento

            // Información del destinatario
            $table->string('recipient_type'); // email, user_id, slack_channel, etc.
            $table->string('recipient_identifier'); // email address, user ID, etc.
            $table->string('recipient_name')->nullable();

            // Información de la notificación
            $table->string('subject')->nullable();
            $table->text('message');
            $table->json('attachments')->nullable();
            $table->string('template_used')->nullable();

            // Estado de entrega
            $table->enum('status', ['pending', 'sent', 'delivered', 'failed', 'bounced', 'opened', 'clicked'])->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();

            // Información de errores
            $table->text('error_message')->nullable();
            $table->json('error_details')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('next_retry_at')->nullable();

            // Información del canal
            $table->string('channel_type'); // email, slack, teams, etc.
            $table->json('channel_response')->nullable(); // Respuesta del proveedor
            $table->string('external_id')->nullable(); // ID externo del proveedor

            // Métricas de rendimiento
            $table->integer('processing_time_ms')->nullable();
            $table->integer('delivery_time_ms')->nullable();

            // Configuración de la notificación
            $table->enum('priority', ['low', 'normal', 'high', 'critical'])->default('normal');
            $table->boolean('is_test')->default(false);
            $table->boolean('is_bulk')->default(false);

            // Metadatos
            $table->json('metadata')->nullable(); // Información adicional específica del canal
            $table->string('batch_id')->nullable(); // Para agrupar notificaciones relacionadas

            $table->timestamps();

            // Índices
            $table->index(['notification_rule_id', 'status']);
            $table->index(['event_type', 'created_at']);
            $table->index(['status', 'sent_at']);
            $table->index(['recipient_identifier', 'event_type']);
            $table->index(['batch_id', 'status']);
            $table->index(['channel_type', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
