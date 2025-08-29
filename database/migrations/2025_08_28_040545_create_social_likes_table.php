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
        Schema::create('social_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('post_id')->nullable()->references('id')->on('social_posts')->onDelete('cascade');
            $table->foreignId('comment_id')->nullable()->references('id')->on('social_comments')->onDelete('cascade');
            $table->string('reaction_type')->default('like');
            $table->timestamps();

            // Un usuario solo puede dar un like por post o comentario
            $table->unique(['user_id', 'post_id', 'reaction_type'], 'user_post_reaction_unique');
            $table->unique(['user_id', 'comment_id', 'reaction_type'], 'user_comment_reaction_unique');
            
            $table->index(['post_id', 'reaction_type']);
            $table->index(['comment_id', 'reaction_type']);
            $table->index(['company_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_likes');
    }
};
