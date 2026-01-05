<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\SocialPost;
use App\Models\SocialComment;
use App\Models\SocialLike;
use App\Models\SocialConnection;
use App\Models\User;
use Illuminate\Database\Seeder;

class SocialNetworkDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Get demo company and user
        $company = Company::where('name', 'GrafiRed Demo')->first();
        $user = User::where('email', 'demo@grafired.test')->first();
        
        if (!$company || !$user) {
            $this->command->info('⚠️  Demo company or user not found. Run setup demo first.');
            return;
        }
        
        // Create some additional demo companies for social interactions
        $companies = collect([
            [
                'name' => 'Papelería Central',
                'email' => 'admin@papeleriacentral.com',
                'phone' => '+57 301 234 5678',
                'address' => 'Carrera 45 #67-89, Medellín',
                'is_active' => true,
            ],
            [
                'name' => 'Impresos del Norte',
                'email' => 'info@impresosnorte.co',
                'phone' => '+57 302 345 6789',
                'address' => 'Calle 123 #45-67, Barranquilla',
                'is_active' => true,
            ],
            [
                'name' => 'Gráficas Modernas',
                'email' => 'contacto@graficasmodernas.com',
                'phone' => '+57 303 456 7890',
                'address' => 'Avenida 68 #123-45, Bogotá',
                'is_active' => true,
            ]
        ]);
        
        $createdCompanies = [];
        foreach ($companies as $companyData) {
            $newCompany = Company::firstOrCreate(['name' => $companyData['name']], $companyData);
            $createdCompanies[] = $newCompany;
            
            // Create a user for each company
            User::firstOrCreate([
                'email' => str_replace('@', '_admin@', $companyData['email']),
            ], [
                'name' => 'Admin ' . $companyData['name'],
                'password' => bcrypt('password'),
                'company_id' => $newCompany->id,
                'is_active' => true,
            ]);
        }
        
        // Create social posts with different types
        $posts = [
            [
                'company_id' => $company->id,
                'user_id' => $user->id,
                'post_type' => 'news',
                'title' => 'Excelente mes de ventas',
                'content' => '🎉 ¡Excelente mes de junio! Hemos superado nuestra meta de ventas en un 23%. Gracias a todos nuestros clientes que confían en nosotros para sus proyectos de impresión. ¡Seguimos creciendo juntos! 📈✨',
                'is_public' => true,
                'created_at' => now()->subHours(2),
                'updated_at' => now()->subHours(2),
            ],
            [
                'company_id' => $createdCompanies[0]->id,
                'user_id' => $createdCompanies[0]->users->first()->id,
                'post_type' => 'materials',
                'title' => 'Oferta Especial - Papel Couché',
                'content' => '📦 NUEVA LLEGADA: Papel couché brillante 150g de excelente calidad. Stock limitado con 15% de descuento para pedidos mayores a 200 pliegos. ¡Aprovecha esta oferta especial! 🎉',
                'is_public' => true,
                'created_at' => now()->subHours(4),
                'updated_at' => now()->subHours(4),
            ],
            [
                'company_id' => $company->id,
                'user_id' => $user->id,
                'post_type' => 'news',
                'title' => 'Trabajo terminado - 50k volantes',
                'content' => '✅ ¡Trabajo terminado! Acabamos de finalizar la impresión de 50,000 volantes para la campaña de verano del cliente. Calidad offset en papel couché 150g con acabado brillante. El cliente quedó muy satisfecho con el resultado. 🖨️👌',
                'is_public' => true,
                'created_at' => now()->subHours(6),
                'updated_at' => now()->subHours(6),
            ],
            [
                'company_id' => $createdCompanies[1]->id,
                'user_id' => $createdCompanies[1]->users->first()->id,
                'post_type' => 'equipment',
                'title' => 'Venta Máquina de Corte',
                'content' => '🔧 Se vende máquina de corte POLAR 92EM en excelente estado. Poco uso, ideal para litografías pequeñas y medianas. Incluye cuchillas adicionales y manual. Precio negociable. Contactar por mensaje privado.',
                'is_public' => true,
                'created_at' => now()->subDays(1),
                'updated_at' => now()->subDays(1),
            ],
            [
                'company_id' => $createdCompanies[2]->id,
                'user_id' => $createdCompanies[2]->users->first()->id,
                'post_type' => 'collaboration',
                'title' => 'Propuesta de Colaboración',
                'content' => '🤝 Buscamos litografías aliadas en Cartagena y alrededores para colaboraciones mutuas. Nos especializamos en trabajos de gran formato y podemos complementar servicios con empresas que manejen trabajos comerciales. ¡Construyamos juntos!',
                'is_public' => true,
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(2),
            ],
            [
                'company_id' => $company->id,
                'user_id' => $user->id,
                'post_type' => 'request',
                'title' => 'Busco Proveedor de Tintas',
                'content' => '🔍 Necesitamos proveedor confiable de tintas para offset. Requerimos entrega quincenal en Cartagena. Preferiblemente con experiencia en el sector gráfico. ¡Esperamos sus propuestas!',
                'is_public' => true,
                'created_at' => now()->subDays(3),
                'updated_at' => now()->subDays(3),
            ]
        ];
        
        $createdPosts = [];
        foreach ($posts as $postData) {
            $post = SocialPost::create($postData);
            $createdPosts[] = $post;
        }
        
        // Create some comments
        $comments = [
            [
                'company_id' => $createdCompanies[0]->id,
                'user_id' => $createdCompanies[0]->users->first()->id,
                'post_id' => $createdPosts[0]->id,
                'content' => '¡Felicitaciones por esos excelentes resultados! La calidad siempre da frutos. 👏',
                'is_public' => true,
            ],
            [
                'company_id' => $createdCompanies[1]->id,
                'user_id' => $createdCompanies[1]->users->first()->id,
                'post_id' => $createdPosts[0]->id,
                'content' => 'Nos alegra ver el crecimiento de la industria gráfica. ¡Sigamos así!',
                'is_public' => true,
            ],
            [
                'company_id' => $company->id,
                'user_id' => $user->id,
                'post_id' => $createdPosts[1]->id,
                'content' => '¿Tienen disponibilidad para entrega en Cartagena? Me interesa la oferta.',
                'is_public' => true,
            ],
            [
                'company_id' => $createdCompanies[2]->id,
                'user_id' => $createdCompanies[2]->users->first()->id,
                'post_id' => $createdPosts[2]->id,
                'content' => 'Excelente trabajo! Se nota la dedicación en cada proyecto.',
                'is_public' => true,
            ]
        ];
        
        foreach ($comments as $commentData) {
            SocialComment::create($commentData);
        }
        
        // Create some likes
        $likes = [
            // Likes on posts
            ['company_id' => $createdCompanies[0]->id, 'user_id' => $createdCompanies[0]->users->first()->id, 'post_id' => $createdPosts[0]->id, 'reaction_type' => 'like'],
            ['company_id' => $createdCompanies[1]->id, 'user_id' => $createdCompanies[1]->users->first()->id, 'post_id' => $createdPosts[0]->id, 'reaction_type' => 'like'],
            ['company_id' => $createdCompanies[2]->id, 'user_id' => $createdCompanies[2]->users->first()->id, 'post_id' => $createdPosts[0]->id, 'reaction_type' => 'love'],
            ['company_id' => $company->id, 'user_id' => $user->id, 'post_id' => $createdPosts[1]->id, 'reaction_type' => 'interested'],
            ['company_id' => $createdCompanies[2]->id, 'user_id' => $createdCompanies[2]->users->first()->id, 'post_id' => $createdPosts[1]->id, 'reaction_type' => 'like'],
            ['company_id' => $createdCompanies[0]->id, 'user_id' => $createdCompanies[0]->users->first()->id, 'post_id' => $createdPosts[2]->id, 'reaction_type' => 'helpful'],
            ['company_id' => $createdCompanies[1]->id, 'user_id' => $createdCompanies[1]->users->first()->id, 'post_id' => $createdPosts[2]->id, 'reaction_type' => 'like'],
        ];
        
        foreach ($likes as $likeData) {
            SocialLike::create($likeData);
        }
        
        // Create some connections
        $connections = [
            [
                'company_id' => $company->id,
                'requester_user_id' => $user->id,
                'target_company_id' => $createdCompanies[0]->id,
                'connection_type' => 'supplier',
                'status' => 'accepted',
                'message' => 'Nos interesa establecer una relación comercial para suministro de papel.',
            ],
            [
                'company_id' => $createdCompanies[1]->id,
                'requester_user_id' => $createdCompanies[1]->users->first()->id,
                'target_company_id' => $company->id,
                'connection_type' => 'collaboration',
                'status' => 'pending',
                'message' => 'Propuesta de colaboración para proyectos de gran formato.',
            ],
            [
                'company_id' => $company->id,
                'requester_user_id' => $user->id,
                'target_company_id' => $createdCompanies[2]->id,
                'connection_type' => 'referral',
                'status' => 'accepted',
                'message' => 'Intercambio de referencias para clientes especializados.',
            ]
        ];
        
        foreach ($connections as $connectionData) {
            SocialConnection::create($connectionData);
        }
        
        $this->command->info('✅ Social Network demo data created successfully!');
        $this->command->info('📊 Created:');
        $this->command->info('   • ' . count($createdCompanies) . ' Additional Companies');
        $this->command->info('   • ' . count($createdPosts) . ' Social Posts (various types)');
        $this->command->info('   • ' . count($comments) . ' Comments');
        $this->command->info('   • ' . count($likes) . ' Likes/Reactions');
        $this->command->info('   • ' . count($connections) . ' Business Connections');
        $this->command->info('');
        $this->command->info('🌐 Social Network is now active!');
    }
}