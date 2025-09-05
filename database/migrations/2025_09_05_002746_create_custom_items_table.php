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
        Schema::create('custom_items', function (Blueprint $table) {
            $table->id();
            
            // Multi-tenancy
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            
            // Información básica
            $table->text('description');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);
            
            // Metadatos
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('company_id');
            $table->index('quantity');
            $table->index(['unit_price', 'total_price']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_items');
    }
};
