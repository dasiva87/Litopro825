<?php

namespace Database\Seeders;

use App\Models\SocialPost;
use App\Models\SocialPostComment;
use App\Models\SocialPostReaction;
use App\Models\User;
use App\Models\Company;
use Illuminate\Database\Seeder;

class SocialPostSeeder extends Seeder
{
    public function run(): void
    {
        // Obtener usuarios y compañías
        $users = User::all();
        $companies = Company::all();

        if ($users->isEmpty() || $companies->isEmpty()) {
            $this->command->warn('No users or companies found. Please seed users and companies first.');
            return;
        }

        $user = $users->first();
        $company = $companies->first();

        // Post 1: Reporte de ventas (tipo news)
        $post1 = SocialPost::create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'post_type' => SocialPost::TYPE_NEWS,
            'title' => 'Reporte de Ventas - Junio 2025',
            'content' => '🎉 ¡Excelente mes de junio! Hemos superado nuestra meta de ventas en un 23%. Gracias a todos nuestros clientes que confían en nosotros para sus proyectos de impresión. ¡Seguimos creciendo juntos! 📈✨',
            'metadata' => [
                'attachment_type' => 'report',
                'report_data' => [
                    'title' => 'Reporte de Ventas - Junio 2025',
                    'subtitle' => '+23% vs mes anterior',
                    'icon' => 'chart'
                ]
            ],
            'is_public' => true,
            'is_featured' => true,
            'tags' => ['ventas', 'reporte', 'junio2025'],
        ]);

        // Post 2: Oferta de servicios
        $post2 = SocialPost::create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'post_type' => SocialPost::TYPE_OFFER,
            'title' => 'Servicio de Impresión Digital Premium',
            'content' => '🖨️ Ofrecemos servicio de impresión digital de alta calidad con acabados profesionales. Ideal para catálogos, revistas y material publicitario. Contamos con tecnología de última generación y entregas rápidas. ¡Cotiza sin compromiso!',
            'is_public' => true,
            'tags' => ['impresion', 'digital', 'catalogo'],
            'contact_info' => [
                'phone' => '+57 300 123 4567',
                'email' => 'ventas@grafired.com',
                'whatsapp' => '+57 300 123 4567'
            ]
        ]);

        // Post 3: Solicitud de materiales
        $post3 = SocialPost::create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'post_type' => SocialPost::TYPE_REQUEST,
            'title' => 'Búsqueda: Papel Couché 150g',
            'content' => '📄 Necesitamos 5000 pliegos de papel couché brillante de 150g, tamaño 70x100cm. Proyecto urgente para catálogo corporativo. Si tienes disponibilidad inmediata, por favor contacta.',
            'is_public' => true,
            'tags' => ['papel', 'couche', 'urgente'],
            'expires_at' => now()->addDays(7),
        ]);

        // Agregar reacciones
        $reactionTypes = [
            SocialPostReaction::TYPE_LIKE,
            SocialPostReaction::TYPE_INTERESTED,
            SocialPostReaction::TYPE_HELPFUL
        ];

        foreach ($users->take(5) as $reactingUser) {
            foreach ([$post1, $post2, $post3] as $post) {
                SocialPostReaction::create([
                    'company_id' => $company->id,
                    'post_id' => $post->id,
                    'user_id' => $reactingUser->id,
                    'reaction_type' => $reactionTypes[array_rand($reactionTypes)]
                ]);
            }
        }

        // Agregar comentarios
        $comments = [
            '¡Excelentes resultados! Felicitaciones por el crecimiento.',
            'Me interesa conocer más sobre sus servicios.',
            '¿Tienen disponibilidad para proyectos grandes?',
            'Muy profesional el trabajo que realizan.',
            '¿Cuáles son sus tiempos de entrega?'
        ];

        foreach ($users->take(3) as $commentingUser) {
            foreach ([$post1, $post2] as $post) {
                SocialPostComment::create([
                    'company_id' => $company->id,
                    'post_id' => $post->id,
                    'user_id' => $commentingUser->id,
                    'content' => $comments[array_rand($comments)],
                    'is_private' => false
                ]);
            }
        }

        $this->command->info('Social posts, reactions, and comments seeded successfully!');
    }
}