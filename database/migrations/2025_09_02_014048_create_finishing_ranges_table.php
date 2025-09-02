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
        Schema::create('finishing_ranges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finishing_id')->constrained()->onDelete('cascade');
            $table->integer('min_quantity');
            $table->integer('max_quantity')->nullable(); // null = sin límite superior
            $table->decimal('range_price', 10, 2);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index(['finishing_id', 'sort_order']);
            $table->index(['finishing_id', 'min_quantity', 'max_quantity']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finishing_ranges');
    }
};
