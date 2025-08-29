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
        Schema::create('social_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('post_id')->references('id')->on('social_posts')->onDelete('cascade');
            $table->foreignId('parent_comment_id')->nullable()->references('id')->on('social_comments')->onDelete('cascade');
            $table->text('content');
            $table->boolean('is_public')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['post_id', 'created_at']);
            $table->index(['parent_comment_id']);
            $table->index(['company_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_comments');
    }
};
