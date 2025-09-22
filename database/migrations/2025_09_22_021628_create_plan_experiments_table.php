<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_experiments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('status', ['draft', 'active', 'paused', 'completed', 'cancelled'])->default('draft');

            // Plan original (control)
            $table->foreignId('control_plan_id')->constrained('plans')->onDelete('cascade');

            // Plan variante (test)
            $table->foreignId('variant_plan_id')->constrained('plans')->onDelete('cascade');

            // Configuración del experimento
            $table->integer('traffic_split')->default(50); // Porcentaje para variante (0-100)
            $table->decimal('confidence_level', 5, 2)->default(95.00); // Nivel de confianza estadística
            $table->integer('min_sample_size')->default(100); // Mínimo de muestras por grupo

            // Fechas del experimento
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->integer('duration_days')->default(30); // Duración planificada

            // Métricas objetivo
            $table->json('target_metrics')->nullable(); // conversion_rate, revenue_per_user, etc.

            // Resultados del experimento
            $table->json('results')->nullable(); // Almacenar resultados estadísticos
            $table->decimal('statistical_significance', 5, 2)->nullable(); // P-value
            $table->string('winner')->nullable(); // 'control', 'variant', 'inconclusive'

            // Metadatos
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->text('notes')->nullable();

            $table->timestamps();

            // Índices
            $table->index(['status', 'started_at']);
            $table->index(['control_plan_id', 'variant_plan_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_experiments');
    }
};