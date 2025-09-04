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
        Schema::create('talonario_sheets', function (Blueprint $table) {
            $table->id();
            
            // Relaciones
            $table->foreignId('talonario_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('simple_item_id')->constrained()->cascadeOnDelete();
            
            // Metadatos de la hoja
            $table->enum('sheet_type', [
                'original',
                'copia_1', 
                'copia_2',
                'copia_3'
            ])->default('original');
            
            $table->integer('sheet_order')->default(1); // 1=original, 2=primera copia, etc.
            $table->string('paper_color', 50)->default('blanco'); // blanco, amarillo, rosado, azul
            $table->text('sheet_notes')->nullable();
            
            $table->timestamps();
            
            // Índices
            $table->index(['talonario_item_id', 'sheet_order']);
            $table->unique(['talonario_item_id', 'sheet_type']); // Una hoja por tipo
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talonario_sheets');
    }
};