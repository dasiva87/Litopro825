<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_channels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['email', 'slack', 'teams', 'discord', 'webhook', 'sms', 'push', 'database'])->default('email');
            $table->enum('status', ['active', 'inactive', 'testing'])->default('active');

            // Configuración del canal
            $table->json('config'); // Configuración específica del tipo de canal
            $table->json('rate_limits')->nullable(); // Límites de envío (por minuto, hora, día)
            $table->json('retry_settings')->nullable(); // Configuración de reintentos

            // Configuración de formato
            $table->string('default_template')->nullable(); // Template por defecto
            $table->json('format_settings')->nullable(); // Configuración de formato (HTML, Markdown, etc.)

            // Filtros y condiciones
            $table->json('filters')->nullable(); // Filtros para determinar qué notificaciones enviar
            $table->json('business_hours')->nullable(); // Horarios de envío
            $table->json('allowed_event_types')->nullable(); // Tipos de eventos permitidos

            // Configuración de prioridad
            $table->integer('priority')->default(1); // 1 = alta, 2 = media, 3 = baja
            $table->boolean('supports_realtime')->default(false);
            $table->boolean('supports_bulk')->default(false);

            // Métricas y monitoreo
            $table->integer('total_sent')->default(0);
            $table->integer('total_delivered')->default(0);
            $table->integer('total_failed')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->text('last_error')->nullable();

            // Metadatos
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->text('notes')->nullable();

            $table->timestamps();

            // Índices
            $table->index(['type', 'status']);
            $table->index(['status', 'priority']);
            $table->index(['created_by', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_channels');
    }
};
