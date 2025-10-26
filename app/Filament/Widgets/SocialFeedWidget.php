<?php

namespace App\Filament\Widgets;

use App\Models\SocialPost;
use App\Models\SocialComment;
use App\Models\SocialLike;
use Filament\Widgets\Widget;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;

class SocialFeedWidget extends Widget
{
    protected string $view = 'filament.widgets.social-feed';
    
    protected static ?int $sort = 8;
    
    protected int | string | array $columnSpan = 'full';
    
    // Widget state properties for Livewire
    public $newPostContent = '';
    public $newPostType = 'news';
    public $showCreatePost = false;
    public $newPostImage = null;

    // Comment system properties
    public $newComments = [];
    public $showAllComments = [];

    // Filter system properties
    public $filterType = 'all';
    public $filterLocation = '';
    public $filterCompany = '';
    public $filterDateFrom = '';
    public $filterDateTo = '';
    public $showFilters = false;

    // Search system properties
    public $searchQuery = '';
    public $searchHashtag = '';
    public $popularHashtags = [];

    // Notifications properties
    public $lastActivityCheck;
    public $newNotifications = 0;
    public $enableRealTimeUpdates = true;
    
    public function toggleCreatePost()
    {
        $this->showCreatePost = !$this->showCreatePost;
        if (!$this->showCreatePost) {
            $this->resetPostForm();
        }
    }
    
    public function createPost()
    {
        $user = auth()->user();
        if (!$user || !$user->company_id) {
            session()->flash('error', 'Debes tener una empresa asociada para crear publicaciones.');
            return;
        }

        $this->validate([
            'newPostContent' => 'required|min:10|max:1000',
            'newPostType' => 'required|in:offer,request,news,equipment,materials,collaboration',
        ]);

        SocialPost::create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'post_type' => $this->newPostType,
            'title' => 'Nueva publicación',  // Título por defecto
            'content' => $this->newPostContent,
            'is_public' => true,
        ]);

        $this->resetPostForm();
        $this->showCreatePost = false;

        // Emit refresh event
        $this->dispatch('post-created');

        session()->flash('social-success', '¡Publicación creada exitosamente!');
    }
    
    public function toggleReaction(int $postId, string $reactionType)
    {
        $user = auth()->user();
        if (!$user || !$user->company_id) {
            session()->flash('error', 'Debes tener una empresa asociada para reaccionar.');
            return;
        }

        // Validar tipo de reacción
        $validReactions = ['like', 'interested', 'helpful', 'contact_me'];
        if (!in_array($reactionType, $validReactions)) {
            session()->flash('error', 'Tipo de reacción inválido.');
            return;
        }

        $existingReaction = \App\Models\SocialPostReaction::where([
            'user_id' => $user->id,
            'post_id' => $postId,
            'reaction_type' => $reactionType
        ])->first();

        if ($existingReaction) {
            $existingReaction->delete();
            $action = 'removed';
        } else {
            // Eliminar cualquier otra reacción del usuario en este post
            \App\Models\SocialPostReaction::where([
                'user_id' => $user->id,
                'post_id' => $postId,
            ])->delete();

            // Crear nueva reacción
            \App\Models\SocialPostReaction::create([
                'company_id' => $user->company_id,
                'user_id' => $user->id,
                'post_id' => $postId,
                'reaction_type' => $reactionType,
            ]);
            $action = 'added';
        }

        // Dispatch event para actualización en tiempo real
        $this->dispatch('post-reaction-changed', [
            'postId' => $postId,
            'reactionType' => $reactionType,
            'action' => $action,
            'userId' => $user->id
        ]);
    }

    // Método legacy para compatibilidad
    public function likePost(int $postId)
    {
        $this->toggleReaction($postId, 'like');
    }

    public function addComment(int $postId)
    {
        $user = auth()->user();
        if (!$user || !$user->company_id) {
            session()->flash('error', 'Debes tener una empresa asociada para comentar.');
            return;
        }

        $commentContent = $this->newComments[$postId] ?? '';
        if (empty(trim($commentContent))) {
            session()->flash('error', 'El comentario no puede estar vacío.');
            return;
        }

        $this->validate([
            "newComments.{$postId}" => 'required|min:1|max:500',
        ], [
            "newComments.{$postId}.required" => 'El comentario es requerido.',
            "newComments.{$postId}.min" => 'El comentario debe tener al menos 1 carácter.',
            "newComments.{$postId}.max" => 'El comentario no puede tener más de 500 caracteres.',
        ]);

        \App\Models\SocialPostComment::create([
            'company_id' => $user->company_id,
            'post_id' => $postId,
            'user_id' => $user->id,
            'content' => $commentContent,
            'is_private' => false,
        ]);

        // Clear the comment input
        $this->newComments[$postId] = '';

        // Dispatch event para actualización en tiempo real
        $this->dispatch('comment-added', [
            'postId' => $postId,
            'userId' => $user->id
        ]);

        session()->flash('comment-success-' . $postId, '¡Comentario agregado exitosamente!');
    }

    public function toggleShowAllComments(int $postId)
    {
        $this->showAllComments[$postId] = !($this->showAllComments[$postId] ?? false);
    }

    public function deleteComment(int $commentId)
    {
        $user = auth()->user();
        if (!$user) {
            session()->flash('error', 'Debes estar autenticado para eliminar comentarios.');
            return;
        }

        $comment = \App\Models\SocialPostComment::find($commentId);
        if (!$comment) {
            session()->flash('error', 'Comentario no encontrado.');
            return;
        }

        // Verificar que el usuario pueda eliminar el comentario
        if ($comment->user_id !== $user->id && !$user->hasAnyRole(['Super Admin', 'Company Admin', 'Manager'])) {
            session()->flash('error', 'No tienes permisos para eliminar este comentario.');
            return;
        }

        $postId = $comment->post_id;
        $comment->delete();

        // Dispatch event para actualización en tiempo real
        $this->dispatch('comment-deleted', [
            'postId' => $postId,
            'commentId' => $commentId
        ]);

        session()->flash('comment-success-' . $postId, 'Comentario eliminado exitosamente.');
    }

    public function toggleFilters()
    {
        $this->showFilters = !$this->showFilters;
    }

    public function clearFilters()
    {
        $this->filterType = 'all';
        $this->filterLocation = '';
        $this->filterCompany = '';
        $this->filterDateFrom = '';
        $this->filterDateTo = '';
    }

    public function applyFilters()
    {
        // Los filtros se aplican automáticamente a través de getRecentPosts()
        session()->flash('filter-success', 'Filtros aplicados exitosamente.');
    }

    public function searchByHashtag($hashtag)
    {
        $this->searchHashtag = $hashtag;
        $this->searchQuery = "#$hashtag";
    }

    public function clearSearch()
    {
        $this->searchQuery = '';
        $this->searchHashtag = '';
    }

    public function loadPopularHashtags()
    {
        try {
            $this->popularHashtags = SocialPost::getPopularHashtags(15);
        } catch (\Exception $e) {
            $this->popularHashtags = [];
            \Log::error('Error loading popular hashtags: ' . $e->getMessage());
        }
    }

    public function mount()
    {
        $this->loadPopularHashtags();
        $this->lastActivityCheck = now();
    }

    // Real-time notifications polling method
    public function checkForUpdates()
    {
        if (!$this->enableRealTimeUpdates) {
            return;
        }

        try {
            $user = auth()->user();
            if (!$user || !$user->company_id) {
                return;
            }

            // Count new activity since last check
            $newPostsCount = SocialPost::where('is_public', true)
                ->where('created_at', '>', $this->lastActivityCheck)
                ->where('company_id', '!=', $user->company_id) // Exclude user's own posts
                ->count();

            $newReactionsCount = \App\Models\SocialPostReaction::whereHas('post', function($query) use ($user) {
                    $query->where('user_id', $user->id); // Reactions to user's posts
                })
                ->where('created_at', '>', $this->lastActivityCheck)
                ->count();

            $newCommentsCount = \App\Models\SocialPostComment::whereHas('post', function($query) use ($user) {
                    $query->where('user_id', $user->id); // Comments on user's posts
                })
                ->where('created_at', '>', $this->lastActivityCheck)
                ->where('user_id', '!=', $user->id) // Exclude user's own comments
                ->count();

            $this->newNotifications = $newPostsCount + $newReactionsCount + $newCommentsCount;
            $this->lastActivityCheck = now();

            // Dispatch browser notification if there are updates
            if ($this->newNotifications > 0) {
                $this->dispatch('new-social-activity', [
                    'count' => $this->newNotifications,
                    'message' => $this->getNotificationMessage()
                ]);
            }

        } catch (\Exception $e) {
            \Log::error('Error checking social feed updates: ' . $e->getMessage());
        }
    }

    public function toggleRealTimeUpdates()
    {
        $this->enableRealTimeUpdates = !$this->enableRealTimeUpdates;

        if ($this->enableRealTimeUpdates) {
            session()->flash('notification-success', 'Notificaciones en tiempo real activadas.');
        } else {
            session()->flash('notification-success', 'Notificaciones en tiempo real desactivadas.');
        }
    }

    private function getNotificationMessage(): string
    {
        if ($this->newNotifications === 1) {
            return 'Nueva actividad en tu red social empresarial';
        }

        return "Hay {$this->newNotifications} nuevas actividades en tu red social empresarial";
    }

    public function markNotificationsAsRead()
    {
        $this->newNotifications = 0;
        $this->lastActivityCheck = now();
    }
    
    public function getRecentPosts()
    {
        try {
            $currentUserId = auth()->id();

            $query = SocialPost::with(['company', 'author', 'reactions', 'comments.author', 'company.city', 'company.state'])
                ->where('is_public', true)
                ->whereNull('expires_at');

            // Apply filters
            if ($this->filterType !== 'all') {
                $query->where('post_type', $this->filterType);
            }

            if (!empty($this->filterCompany)) {
                $query->whereHas('company', function($q) {
                    $q->where('name', 'LIKE', '%' . $this->filterCompany . '%');
                });
            }

            if (!empty($this->filterLocation)) {
                $query->whereHas('company', function($q) {
                    $q->whereHas('city', function($cityQuery) {
                        $cityQuery->where('name', 'LIKE', '%' . $this->filterLocation . '%');
                    })->orWhereHas('state', function($stateQuery) {
                        $stateQuery->where('name', 'LIKE', '%' . $this->filterLocation . '%');
                    });
                });
            }

            if (!empty($this->filterDateFrom)) {
                $query->whereDate('created_at', '>=', $this->filterDateFrom);
            }

            if (!empty($this->filterDateTo)) {
                $query->whereDate('created_at', '<=', $this->filterDateTo);
            }

            // Apply search
            if (!empty($this->searchQuery)) {
                if (!empty($this->searchHashtag)) {
                    // Búsqueda específica por hashtag
                    $query->withHashtag($this->searchHashtag);
                } else {
                    // Búsqueda semántica general
                    $query->search($this->searchQuery);
                }
            }

            $posts = $query->orderBy('created_at', 'desc')
                ->limit(20)
                ->get();

            return $posts->map(function ($post) use ($currentUserId) {
                try {
                    $reactionsCounts = $post->getReactionsCounts();
                    $userReaction = $currentUserId ? $post->reactions->where('user_id', $currentUserId)->first() : null;

                    return [
                        'id' => $post->id,
                        'content' => $post->content ?? '',
                        'formatted_content' => $post->getFormattedContent(),
                        'hashtags' => $post->tags ?? [],
                        'post_type' => $post->post_type ?? 'news',
                        'post_type_label' => $post->getPostTypeLabel(),
                        'post_type_color' => $post->getPostTypeColor(),
                        'company_name' => $post->company ? $post->company->name : 'Empresa Desconocida',
                        'author_name' => $post->author ? $post->author->name : 'Usuario Desconocido',
                        'created_at' => $post->created_at,
                        'created_at_human' => $post->created_at->diffForHumans(),

                        // Contadores de reacciones por tipo
                        'reactions_count' => [
                            'like' => $reactionsCounts['like'] ?? 0,
                            'interested' => $reactionsCounts['interested'] ?? 0,
                            'helpful' => $reactionsCounts['helpful'] ?? 0,
                            'contact_me' => $reactionsCounts['contact_me'] ?? 0,
                        ],
                        'total_reactions_count' => $post->reactions ? $post->reactions->count() : 0,
                        'user_reaction' => $userReaction ? $userReaction->reaction_type : null,

                        // Legacy support
                        'likes_count' => $reactionsCounts['like'] ?? 0,
                        'user_liked' => $userReaction && $userReaction->reaction_type === 'like',

                        'comments_count' => $post->comments ? $post->comments->count() : 0,
                        'can_edit' => $currentUserId && $post->user_id === $currentUserId,
                        'avatar_initials' => ($post->company && $post->company->name) ? strtoupper(substr($post->company->name, 0, 2)) : '??',
                        'avatar_url' => ($post->company && $post->company->avatar)
                            ? Storage::disk('public')->url($post->company->avatar)
                            : null,
                        'company_name_full' => $post->company ? $post->company->name : 'Empresa Desconocida',
                        'recent_comments' => $post->comments ? $post->comments->sortByDesc('created_at')->take(3)->map(function ($comment) use ($currentUserId) {
                            return [
                                'id' => $comment->id,
                                'content' => $comment->content ?? '',
                                'author_name' => $comment->author ? $comment->author->name : 'Usuario Desconocido',
                                'created_at_human' => $comment->created_at->diffForHumans(),
                                'can_delete' => $currentUserId && ($comment->user_id === $currentUserId || auth()->user()?->hasAnyRole(['Super Admin', 'Company Admin', 'Manager'])),
                                'company_name' => $comment->company ? $comment->company->name : 'Empresa Desconocida',
                            ];
                        }) : collect(),
                        'all_comments' => $post->comments ? $post->comments->sortBy('created_at')->map(function ($comment) use ($currentUserId) {
                            return [
                                'id' => $comment->id,
                                'content' => $comment->content ?? '',
                                'author_name' => $comment->author ? $comment->author->name : 'Usuario Desconocido',
                                'created_at_human' => $comment->created_at->diffForHumans(),
                                'can_delete' => $currentUserId && ($comment->user_id === $currentUserId || auth()->user()?->hasAnyRole(['Super Admin', 'Company Admin', 'Manager'])),
                                'company_name' => $comment->company ? $comment->company->name : 'Empresa Desconocida',
                            ];
                        }) : collect(),
                        'show_all_comments' => $this->showAllComments[$post->id] ?? false,
                    ];
                } catch (\Exception $e) {
                    \Log::error('Error processing post ID ' . $post->id . ': ' . $e->getMessage());
                    return null; // Skip this post
                }
            })->filter();
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('SocialFeedWidget error: ' . $e->getMessage());

            // Return empty collection to prevent widget from breaking
            return collect();
        }
    }
    
    public function getPostTypes()
    {
        return SocialPost::getPostTypes();
    }
    
    private function resetPostForm()
    {
        $this->newPostContent = '';
        $this->newPostType = 'news';
    }
    
    public function getViewData(): array
    {
        return [
            'posts' => $this->getRecentPosts(),
            'postTypes' => $this->getPostTypes(),
            'showCreatePost' => $this->showCreatePost,
        ];
    }
}