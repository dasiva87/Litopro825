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
        Schema::create('talonario_items', function (Blueprint $table) {
            $table->id();
            
            // Información básica
            $table->text('description');
            $table->integer('quantity')->default(1); // Cantidad de talonarios solicitados
            
            // Configuración de numeración
            $table->integer('numero_inicial')->default(1);
            $table->integer('numero_final');
            $table->integer('numeros_por_talonario')->default(25);
            $table->string('prefijo', 10)->default('Nº');
            
            // Dimensiones del talonario completo
            $table->decimal('ancho', 8, 2); // En cm
            $table->decimal('alto', 8, 2);  // En cm
            
            // Costos calculados automáticamente
            $table->decimal('sheets_total_cost', 10, 2)->default(0);
            $table->decimal('finishing_cost', 10, 2)->default(0);
            $table->decimal('design_value', 10, 2)->default(0);
            $table->decimal('transport_value', 10, 2)->default(0);
            $table->decimal('profit_percentage', 5, 2)->default(25);
            $table->decimal('total_cost', 10, 2)->default(0);
            $table->decimal('final_price', 10, 2)->default(0);
            
            // Metadatos
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index(['numero_inicial', 'numero_final']);
            $table->index('quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talonario_items');
    }
};