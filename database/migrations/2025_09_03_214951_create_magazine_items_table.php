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
        Schema::create('magazine_items', function (Blueprint $table) {
            $table->id();
            
            // Información básica
            $table->text('description');
            $table->decimal('quantity', 10, 2);
            
            // Dimensiones revista cerrada
            $table->decimal('closed_width', 8, 2);
            $table->decimal('closed_height', 8, 2);
            
            // Encuadernación
            $table->enum('binding_type', [
                'grapado', 
                'plegado', 
                'anillado', 
                'cosido', 
                'caballete', 
                'lomo',
                'espiral',
                'wire_o',
                'hotmelt'
            ]);
            $table->enum('binding_side', ['arriba', 'izquierda', 'derecha', 'abajo']);
            
            // Costos adicionales
            $table->decimal('binding_cost', 10, 2)->default(0);
            $table->decimal('assembly_cost', 10, 2)->default(0);
            $table->decimal('finishing_cost', 10, 2)->default(0);
            $table->decimal('transport_value', 10, 2)->default(0);
            $table->decimal('design_value', 10, 2)->default(0);
            $table->decimal('profit_percentage', 5, 2)->default(25);
            
            // Costos calculados
            $table->decimal('pages_total_cost', 10, 2)->default(0);
            $table->decimal('total_cost', 10, 2)->default(0);
            $table->decimal('final_price', 10, 2)->default(0);
            
            // Metadatos
            $table->json('binding_options')->nullable(); // Opciones específicas del tipo de encuadernación
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('magazine_items');
    }
};
