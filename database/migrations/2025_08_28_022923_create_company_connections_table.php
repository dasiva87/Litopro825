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
        Schema::create('company_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('connected_company_id')->constrained('companies')->onDelete('cascade');
            $table->enum('connection_type', ['partner', 'supplier', 'client', 'collaborator', 'network']);
            $table->enum('status', ['pending', 'approved', 'rejected', 'blocked'])->default('pending');
            $table->foreignId('requested_by_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->json('connection_metadata')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            
            $table->unique(['company_id', 'connected_company_id']);
            $table->index(['company_id', 'status']);
            $table->index(['connected_company_id', 'status']);
            $table->index(['connection_type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_connections');
    }
};
