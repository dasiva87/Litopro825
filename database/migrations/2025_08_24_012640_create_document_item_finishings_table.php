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
        Schema::create('document_item_finishings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_item_id')->constrained()->cascadeOnDelete();
            $table->string('finishing_name'); // Nombre del acabado
            $table->decimal('quantity', 10, 2)->default(1.00);
            $table->boolean('is_double_sided')->default(false);
            $table->decimal('unit_price', 10, 4)->default(0.0000);
            $table->decimal('total_price', 12, 2)->default(0.00);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_item_finishings');
    }
};
