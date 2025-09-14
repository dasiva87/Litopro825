<?php

namespace App\Filament\Widgets;

use App\Models\SocialPost;
use App\Models\SocialPostReaction;
use App\Models\City;
use App\Services\NotificationService;
use Filament\Widgets\Widget;
use Livewire\Component;
use Carbon\Carbon;

class SocialPostWidget extends Widget
{
    protected string $view = 'filament.widgets.social-post-widget';

    protected int | string | array $columnSpan = 'full';

    protected $listeners = ['post-created' => 'refreshPosts'];

    // Filtros
    public $filterType = '';
    public $filterCity = '';
    public $filterDateFrom = '';
    public $filterDateTo = '';
    public $filterSearch = '';

    public function getSocialPosts()
    {
        $query = SocialPost::with(['author.company.city', 'reactions', 'comments.author'])
            ->public()
            ->notExpired();

        // Filtro por tipo de post
        if (!empty($this->filterType)) {
            $query->where('post_type', $this->filterType);
        }

        // Filtro por ciudad
        if (!empty($this->filterCity)) {
            $query->whereHas('author.company', function ($q) {
                $q->where('city_id', $this->filterCity);
            });
        }

        // Filtro por fecha desde
        if (!empty($this->filterDateFrom)) {
            $query->whereDate('created_at', '>=', $this->filterDateFrom);
        }

        // Filtro por fecha hasta
        if (!empty($this->filterDateTo)) {
            $query->whereDate('created_at', '<=', $this->filterDateTo);
        }

        // Filtro por búsqueda en contenido
        if (!empty($this->filterSearch)) {
            $searchTerm = '%' . $this->filterSearch . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('content', 'LIKE', $searchTerm)
                  ->orWhere('title', 'LIKE', $searchTerm);
            });
        }

        return $query->recent()
            ->limit(20)
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
        $user = auth()->user();
        $companyId = $user->company_id;

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

            // Enviar notificación al autor del post
            $notificationService = app(NotificationService::class);
            $notificationService->notifyNewReaction($post, $reactionType, $user);

            // Emitir evento para actualizar notificaciones
            $this->dispatch('notifications-updated');
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
        $user = auth()->user();

        $newComment = $post->comments()->create([
            'company_id' => $user->company_id,
            'user_id' => auth()->id(),
            'content' => trim($comment),
            'is_private' => false
        ]);

        // Enviar notificación al autor del post
        $notificationService = app(NotificationService::class);
        $notificationService->notifyNewComment($post, [
            'id' => $newComment->id,
            'content' => trim($comment)
        ], $user);

        // Emitir evento para actualizar notificaciones
        $this->dispatch('notifications-updated');

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

    public function clearFilters()
    {
        $this->filterType = '';
        $this->filterCity = '';
        $this->filterDateFrom = '';
        $this->filterDateTo = '';
        $this->filterSearch = '';
        $this->dispatch('$refresh');
    }

    public function getPostTypes()
    {
        return [
            'news' => 'Noticia',
            'offer' => 'Oferta de Servicios',
            'request' => 'Solicitud',
            'equipment' => 'Equipo',
            'materials' => 'Materiales',
            'collaboration' => 'Colaboración'
        ];
    }

    public function getCities()
    {
        return City::where('is_active', 1)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }
}