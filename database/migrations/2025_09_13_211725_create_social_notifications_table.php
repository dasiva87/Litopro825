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
        Schema::create('social_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Usuario que recibe la notificación
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade'); // Usuario que genera la acción
            $table->enum('type', ['new_post', 'post_comment', 'post_reaction', 'post_mention']);
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable(); // Datos adicionales (post_id, comment_id, etc.)
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'user_id', 'read_at']);
            $table->index(['company_id', 'type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_notifications');
    }
};
