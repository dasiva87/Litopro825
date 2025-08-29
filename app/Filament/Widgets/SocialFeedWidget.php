<?php

namespace App\Filament\Widgets;

use App\Models\SocialPost;
use App\Models\SocialComment;
use App\Models\SocialLike;
use Filament\Widgets\Widget;
use Illuminate\Contracts\View\View;

class SocialFeedWidget extends Widget
{
    protected string $view = 'filament.widgets.social-feed';
    
    protected static ?int $sort = 4;
    
    protected int | string | array $columnSpan = 'full';
    
    // Widget state properties for Livewire
    public $newPostContent = '';
    public $newPostType = 'news';
    public $showCreatePost = false;
    
    public function toggleCreatePost()
    {
        $this->showCreatePost = !$this->showCreatePost;
        if (!$this->showCreatePost) {
            $this->resetPostForm();
        }
    }
    
    public function createPost()
    {
        $this->validate([
            'newPostContent' => 'required|min:10|max:1000',
            'newPostType' => 'required|in:offer,request,news,equipment,materials,collaboration',
        ]);
        
        SocialPost::create([
            'company_id' => auth()->user()->company_id,
            'user_id' => auth()->id(),
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
    
    public function likePost(int $postId)
    {
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
    
    public function getRecentPosts()
    {
        try {
            $posts = SocialPost::with(['company', 'author', 'likes', 'comments.author'])
                ->where('is_public', true)
                ->whereNull('expires_at')
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get();

            return $posts->map(function ($post) {
                return [
                    'id' => $post->id,
                    'content' => $post->content ?? '',
                    'post_type' => $post->post_type ?? 'news',
                    'post_type_label' => $post->getPostTypeLabel(),
                    'post_type_color' => $post->getPostTypeColor(),
                    'company_name' => optional($post->company)->name ?? 'Empresa Desconocida',
                    'author_name' => optional($post->author)->name ?? 'Usuario Desconocido',
                    'created_at' => $post->created_at,
                    'created_at_human' => $post->created_at->diffForHumans(),
                    'likes_count' => $post->likes ? $post->likes->count() : 0,
                    'comments_count' => $post->comments ? $post->comments->count() : 0,
                    'user_liked' => $post->likes ? $post->likes->where('user_id', auth()->id())->where('reaction_type', 'like')->isNotEmpty() : false,
                    'can_edit' => $post->user_id === auth()->id(),
                    'avatar_initials' => optional($post->company)->name ? substr($post->company->name, 0, 1) : '?',
                    'recent_comments' => $post->comments ? $post->comments->take(3)->map(function ($comment) {
                        return [
                            'id' => $comment->id,
                            'content' => $comment->content ?? '',
                            'author_name' => optional($comment->author)->name ?? 'Usuario Desconocido',
                            'created_at_human' => $comment->created_at->diffForHumans(),
                        ];
                    }) : collect(),
                ];
            });
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