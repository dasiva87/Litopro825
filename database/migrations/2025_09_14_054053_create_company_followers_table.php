<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_followers', function (Blueprint $table) {
            $table->id();

            // Empresa que sigue (follower)
            $table->foreignId('follower_company_id')
                ->constrained('companies')
                ->onDelete('cascade');

            // Empresa que es seguida (following)
            $table->foreignId('followed_company_id')
                ->constrained('companies')
                ->onDelete('cascade');

            // Usuario que realizó la acción
            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');

            $table->timestamps();

            // Índices para optimización
            $table->index(['follower_company_id', 'followed_company_id']);
            $table->index(['followed_company_id', 'follower_company_id']);
            $table->index('created_at');

            // Evitar duplicados
            $table->unique(['follower_company_id', 'followed_company_id'], 'unique_company_follow');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_followers');
    }
};