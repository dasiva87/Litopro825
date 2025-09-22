<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automated_report_id')->constrained('automated_reports')->onDelete('cascade');

            // Información de la ejecución
            $table->enum('status', ['pending', 'running', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('execution_time_seconds')->nullable();

            // Resultados de la ejecución
            $table->json('generated_data')->nullable(); // Datos calculados
            $table->text('file_path')->nullable(); // Ruta del archivo generado
            $table->string('file_format')->nullable(); // Formato del archivo
            $table->integer('file_size_bytes')->nullable(); // Tamaño del archivo
            $table->string('file_hash')->nullable(); // Hash para verificación de integridad

            // Información de entrega
            $table->json('delivery_status')->nullable(); // Estado de cada método de entrega
            $table->integer('recipients_count')->default(0);
            $table->integer('successful_deliveries')->default(0);
            $table->integer('failed_deliveries')->default(0);

            // Métricas de la ejecución
            $table->integer('rows_processed')->nullable();
            $table->integer('records_included')->nullable();
            $table->json('data_period')->nullable(); // Período de datos incluidos (from, to)

            // Errores y logs
            $table->text('error_message')->nullable();
            $table->json('execution_log')->nullable(); // Log detallado de la ejecución
            $table->json('warnings')->nullable(); // Advertencias durante la ejecución

            // Comparación con ejecución anterior
            $table->boolean('has_significant_changes')->nullable();
            $table->json('change_summary')->nullable(); // Resumen de cambios vs ejecución anterior
            $table->decimal('variance_percentage', 8, 4)->nullable(); // Variación porcentual

            // Información del trigger
            $table->enum('trigger_type', ['scheduled', 'manual', 'api', 'event'])->default('scheduled');
            $table->foreignId('triggered_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();

            // Índices
            $table->index(['automated_report_id', 'status']);
            $table->index(['status', 'started_at']);
            $table->index(['completed_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_executions');
    }
};
