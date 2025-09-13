<?php

namespace App\Filament\Widgets;

use App\Models\SocialPost;
use App\Models\SocialPostReaction;
use Filament\Widgets\Widget;
use Livewire\Component;

class SocialPostWidget extends Widget
{
    protected string $view = 'filament.widgets.social-post-widget';

    protected int | string | array $columnSpan = 'full';

    protected $listeners = ['post-created' => 'refreshPosts'];

    public function getSocialPosts()
    {
        return SocialPost::with(['author', 'reactions', 'comments.author'])
            ->public()
            ->notExpired()
            ->recent()
            ->limit(10)
            ->get();
    }

    public function refreshPosts()
    {
        // Este método se ejecuta cuando se recibe el evento 'post-created'
        // Livewire automáticamente re-renderiza el componente
        $this->dispatch('$refresh');
    }

    public function toggleReaction($postId, $reactionType)
    {
        $post = SocialPost::findOrFail($postId);
        $userId = auth()->id();
        $companyId = auth()->user()->company_id;

        // Verificar si ya tiene esta reacción
        $existingReaction = $post->reactions()
            ->where('user_id', $userId)
            ->where('reaction_type', $reactionType)
            ->first();

        if ($existingReaction) {
            // Si ya tiene esta reacción, la quita
            $existingReaction->delete();
        } else {
            // Eliminar cualquier reacción previa del usuario en este post
            $post->reactions()->where('user_id', $userId)->delete();

            // Agregar nueva reacción
            SocialPostReaction::create([
                'company_id' => $companyId,
                'post_id' => $postId,
                'user_id' => $userId,
                'reaction_type' => $reactionType
            ]);
        }

        // Recargar el componente
        $this->dispatch('$refresh');
    }

    public function addComment($postId, $comment)
    {
        if (empty(trim($comment))) {
            return;
        }

        $post = SocialPost::findOrFail($postId);

        $post->comments()->create([
            'company_id' => auth()->user()->company_id,
            'user_id' => auth()->id(),
            'content' => trim($comment),
            'is_private' => false
        ]);

        // Recargar el componente
        $this->dispatch('$refresh');
    }

    public function hasUserReacted($post, $reactionType)
    {
        if (!auth()->check()) return false;

        return $post->reactions()
            ->where('user_id', auth()->id())
            ->where('reaction_type', $reactionType)
            ->exists();
    }

    public function getReactionCounts($post)
    {
        return $post->reactions()
            ->selectRaw('reaction_type, count(*) as count')
            ->groupBy('reaction_type')
            ->pluck('count', 'reaction_type')
            ->toArray();
    }
}