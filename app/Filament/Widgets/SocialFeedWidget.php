<?php

namespace App\Filament\Widgets;

use App\Models\SocialPost;
use App\Models\SocialComment;
use App\Models\SocialLike;
use App\Models\Company;
use App\Models\Contact;
use Filament\Widgets\Widget;
use Illuminate\Contracts\View\View;
use Livewire\WithFileUploads;

class SocialFeedWidget extends Widget
{
    use WithFileUploads;
    
    protected string $view = 'filament.widgets.social-feed';
    
    protected static ?int $sort = 4;
    
    protected int | string | array $columnSpan = 'full';
    
    // Widget state properties for Livewire
    public $newPostContent = '';
    public $newPostType = 'public';
    public $attachedFile;
    public $showCreatePost = true;
    
    // Demo data properties
    public $demoMode = true;
    public $searchQuery = '';
    public $selectedFilter = 'all';
    public $isLoadingPosts = false;
    
    public function toggleCreatePost()
    {
        $this->showCreatePost = !$this->showCreatePost;
        if (!$this->showCreatePost) {
            $this->resetPostForm();
        }
    }
    
    public function triggerFileUpload($type = 'image')
    {
        $this->dispatch('triggerFileUpload', type: $type);
    }
    
    public function removeAttachment()
    {
        $this->attachedFile = null;
    }
    
    public function createPost()
    {
        // Simulate network delay for better UX demonstration
        sleep(1);
        
        $this->validate([
            'newPostContent' => 'required|min:5|max:2000',
            'newPostType' => 'required|in:public,network,city,private',
        ]);
        
        // In demo mode, just show success message
        if ($this->demoMode) {
            $this->resetPostForm();
            $this->dispatch('post-created');
            session()->flash('social-success', '¡Post publicado exitosamente! (Demo)');
            return;
        }
        
        // Real implementation would go here
        SocialPost::create([
            'company_id' => auth()->user()->company_id,
            'user_id' => auth()->id(),
            'post_type' => $this->newPostType,
            'title' => 'Nueva publicación',
            'content' => $this->newPostContent,
            'is_public' => true,
        ]);
        
        $this->resetPostForm();
        $this->dispatch('post-created');
        session()->flash('social-success', '¡Publicación creada exitosamente!');
    }
    
    public function likePost(int $postId)
    {
        // Simulate loading for better UX
        sleep(0.5);
        
        $existingLike = SocialLike::where([
            'user_id' => auth()->id(),
            'post_id' => $postId,
            'reaction_type' => 'like'
        ])->first();
        
        if ($existingLike) {
            $existingLike->delete();
        } else {
            SocialLike::create([
                'company_id' => auth()->user()->company_id,
                'user_id' => auth()->id(),
                'post_id' => $postId,
                'reaction_type' => 'like',
            ]);
        }
        
        $this->dispatch('post-liked', postId: $postId);
    }
    
    public function loadMorePosts()
    {
        $this->isLoadingPosts = true;
        // Simulate loading delay
        sleep(2);
        $this->isLoadingPosts = false;
        
        // In a real app, you would load more posts from database
        $this->dispatch('posts-loaded');
        session()->flash('social-success', '¡Nuevas publicaciones cargadas!');
    }
    
    public function refreshFeed()
    {
        // Simulate refreshing feed
        sleep(1);
        $this->dispatch('feed-refreshed');
        session()->flash('social-success', '¡Feed actualizado!');
    }
    
    public function updateFeedData()
    {
        // This method will be called by wire:poll automatically
        // In a real app, you would check for new posts from database
        $this->dispatch('feed-updated');
    }
    
    public function updatedSearchQuery()
    {
        // This method runs automatically when searchQuery property changes
        // Perfect for real-time search functionality
        if (strlen($this->searchQuery) > 2) {
            $this->dispatch('search-updated', query: $this->searchQuery);
        }
    }
    
    public function getRecentPosts()
    {
        // Return demo posts matching the mockup design
        return collect([
            [
                'id' => 1,
                'company_name' => 'Carlos Ventas - Litografía Demo',
                'avatar_bg' => 'bg-blue-600',
                'avatar_initials' => 'CV',
                'time' => 'Hace 2 horas',
                'visibility' => 'Público',
                'visibility_icon' => 'fas fa-globe-americas',
                'content' => '🎉 ¡Excelente mes de junio! Hemos superado nuestra meta de ventas en un 23%. Gracias a todos nuestros clientes que confían en nosotros para sus proyectos de impresión. ¡Seguimos creciendo juntos! 📈✨',
                'has_image' => true,
                'image_type' => 'chart',
                'likes_count' => 13,
                'comments_count' => 5,
                'shares_count' => 2,
                'user_liked' => false,
                'show_comments' => false
            ],
            [
                'id' => 2,
                'company_name' => 'Papelería Central',
                'avatar_bg' => 'bg-green-600',
                'avatar_initials' => 'PC',
                'time' => 'Hace 4 horas',
                'visibility' => 'Bogotá',
                'visibility_icon' => 'fas fa-map-marker-alt',
                'content' => '',
                'special_type' => 'stock_alert',
                'alert_title' => 'NUEVA LLEGADA',
                'alert_content' => '📦 Papel couché brillante 150g de excelente calidad. Stock limitado con 15% de descuento para pedidos mayores a 200 pliegos. ¡Aprovecha esta oferta especial! 🎯',
                'product_details' => [
                    'Gramaje' => '150g/m²',
                    'Formato' => '70x100cm',
                    'Stock' => '500 pliegos',
                    'Precio' => '$850/pliego'
                ],
                'likes_count' => 12,
                'comments_count' => 8,
                'orders_count' => 3,
                'user_liked' => false,
                'show_order_button' => true
            ],
            [
                'id' => 3,
                'company_name' => 'José Producción - Litografía Demo',
                'avatar_bg' => 'bg-purple-600',
                'avatar_initials' => 'JP',
                'time' => 'Hace 6 horas',
                'visibility' => 'Mi Red',
                'visibility_icon' => 'fas fa-users',
                'content' => '✅ ¡Trabajo terminado! Acabamos de finalizar la impresión de 50.000 volantes para la campaña de verano del cliente. Calidad offset en papel couché 150g con acabado brillante. El cliente quedó muy satisfecho con el resultado. 👍💼',
                'has_image' => true,
                'image_type' => 'work_completed',
                'likes_count' => 22,
                'comments_count' => 12,
                'shares_count' => 4,
                'user_liked' => true,
                'show_comments' => true,
                'recent_comments' => [
                    [
                        'author' => 'Carlos Ventas',
                        'avatar_bg' => 'bg-blue-500',
                        'avatar_initials' => 'CV',
                        'content' => 'Excelente trabajo! Se ve la calidad en cada detalle 👏',
                        'time' => 'Hace 2h'
                    ]
                ]
            ]
        ]);
    }
    
    public function getPostTypes()
    {
        return [
            'public' => '🌍 Público',
            'network' => '👥 Mi Red',
            'city' => '🏙️ Mi Ciudad',
            'private' => '🔒 Solo Yo'
        ];
    }
    
    public function getSuggestedCompanies()
    {
        return collect([
            [
                'name' => 'Imprenta Gráfica',
                'location' => 'Medellín • 45 seguidores',
                'avatar_bg' => 'bg-indigo-500',
                'avatar_initials' => 'IG'
            ],
            [
                'name' => 'Papeles y Diseños',
                'location' => 'Cali • 28 seguidores',
                'avatar_bg' => 'bg-red-500',
                'avatar_initials' => 'PD'
            ],
            [
                'name' => 'Diseño & Marketing',
                'location' => 'Barranquilla • 67 seguidores',
                'avatar_bg' => 'bg-yellow-500',
                'avatar_initials' => 'DM'
            ]
        ]);
    }
    
    public function getTrends()
    {
        return collect([
            ['tag' => '#ImpresionOffset', 'posts' => 45],
            ['tag' => '#PapelCouche', 'posts' => 32],
            ['tag' => '#Serigrafía', 'posts' => 28],
            ['tag' => '#ArtesGráficas', 'posts' => 67]
        ]);
    }
    
    public function getChatContacts()
    {
        return collect([
            [
                'name' => 'Papelería Central',
                'status' => 'online',
                'avatar_bg' => 'bg-green-500',
                'avatar_initials' => 'PC',
                'last_seen' => 'En línea',
                'unread' => true
            ],
            [
                'name' => 'Imprenta Gráfica',
                'status' => 'away',
                'avatar_bg' => 'bg-purple-500',
                'avatar_initials' => 'IG',
                'last_seen' => 'Hace 5 min',
                'unread' => false
            ],
            [
                'name' => 'Tintas y Diseños',
                'status' => 'offline',
                'avatar_bg' => 'bg-blue-500',
                'avatar_initials' => 'TD',
                'last_seen' => 'Hace 1 hora',
                'unread' => false
            ]
        ]);
    }
    
    private function resetPostForm()
    {
        $this->newPostContent = '';
        $this->newPostType = 'public';
    }
    
    public function getNotifications()
    {
        return collect([
            [
                'id' => 1,
                'type' => 'like',
                'message' => 'Carlos Ventas le gustó tu publicación',
                'time' => 'Hace 2 min',
                'avatar_bg' => 'bg-blue-600',
                'avatar_initials' => 'CV',
                'unread' => true
            ],
            [
                'id' => 2,
                'type' => 'comment',
                'message' => 'Nueva oferta de papel disponible en tu área',
                'time' => 'Hace 15 min',
                'avatar_bg' => 'bg-green-600', 
                'avatar_initials' => 'PC',
                'unread' => true
            ],
            [
                'id' => 3,
                'type' => 'follow',
                'message' => 'Imprenta Gráfica comenzó a seguirte',
                'time' => 'Hace 1 hora',
                'avatar_bg' => 'bg-purple-600',
                'avatar_initials' => 'IG',
                'unread' => false
            ]
        ]);
    }

    public function getViewData(): array
    {
        return [
            'posts' => $this->getRecentPosts(),
            'postTypes' => $this->getPostTypes(),
            'suggestedCompanies' => $this->getSuggestedCompanies(),
            'trends' => $this->getTrends(),
            'chatContacts' => $this->getChatContacts(),
            'notifications' => $this->getNotifications(),
            'showCreatePost' => $this->showCreatePost,
            'currentUser' => [
                'name' => auth()->user()->name,
                'avatar_initials' => strtoupper(substr(auth()->user()->name, 0, 2))
            ]
        ];
    }
}