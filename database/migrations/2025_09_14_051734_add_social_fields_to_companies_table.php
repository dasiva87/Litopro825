<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Perfil social
            $table->text('bio')->nullable()->after('website');
            $table->string('avatar')->nullable()->after('bio');
            $table->string('banner')->nullable()->after('avatar');
            $table->string('facebook')->nullable()->after('banner');
            $table->string('instagram')->nullable()->after('facebook');
            $table->string('twitter')->nullable()->after('instagram');
            $table->string('linkedin')->nullable()->after('twitter');

            // Configuraciones de privacidad
            $table->boolean('is_public')->default(true)->after('linkedin');
            $table->boolean('allow_followers')->default(true)->after('is_public');
            $table->boolean('show_contact_info')->default(true)->after('allow_followers');

            // Estadísticas
            $table->integer('followers_count')->default(0)->after('show_contact_info');
            $table->integer('following_count')->default(0)->after('followers_count');
            $table->integer('posts_count')->default(0)->after('following_count');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'bio',
                'avatar',
                'banner',
                'facebook',
                'instagram',
                'twitter',
                'linkedin',
                'is_public',
                'allow_followers',
                'show_contact_info',
                'followers_count',
                'following_count',
                'posts_count'
            ]);
        });
    }
};