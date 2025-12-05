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
        Schema::create('client_relationships', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_company_id'); // Mi empresa (que ofrece servicios)
            $table->unsignedBigInteger('client_company_id');   // Cliente Grafired
            $table->unsignedBigInteger('approved_by_user_id');
            $table->timestamp('approved_at')->useCurrent();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('supplier_company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('client_company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('approved_by_user_id')->references('id')->on('users')->onDelete('cascade');
            
            // Índices
            $table->unique(['supplier_company_id', 'client_company_id'], 'unique_client_relationship');
            $table->index(['supplier_company_id', 'is_active'], 'client_relationships_supplier_active');
            $table->index(['client_company_id', 'is_active'], 'client_relationships_client_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_relationships');
    }
};