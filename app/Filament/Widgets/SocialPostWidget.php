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

    protected static ?int $sort = 8;

    protected int | string | array $columnSpan = 'full';

    protected $listeners = ['post-created' => 'refreshPosts'];

    // Mostrar filtros (false = oculto por defecto, true = visible)
    public bool $showFilters = false;

    // Paginación
    public int $perPage = 10;
    public int $page = 1;
    public bool $hasMorePages = true;
    public $loadedPosts = []; // Almacenar posts cargados

    // Filtros
    public $filterType = '';
    public $filterCity = '';
    public $filterDateFrom = '';
    public $filterDateTo = '';
    public $filterSearch = '';

    public function mount()
    {
        // Cargar posts iniciales
        $this->loadPosts();
    }

    public function loadPosts()
    {
        $userCompanyId = auth()->user()?->company_id;

        // Remover el scope global para permitir posts cross-tenant
        $query = SocialPost::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
            ->with([
                'author.company.city',
                'company',
                'reactions',
                'comments.author'
            ])
            ->forFeed($userCompanyId); // Usar el nuevo scope personalizado

        // Filtro por tipo de post
        if (!empty($this->filterType)) {
            $query->where('post_type', $this->filterType);
        }

        // Filtro por ciudad
        if (!empty($this->filterCity)) {
            $query->whereHas('company', function ($q) {
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

        // Paginación
        $posts = $query->recent()
            ->skip(($this->page - 1) * $this->perPage)
            ->take($this->perPage + 1) // +1 para saber si hay más páginas
            ->get();

        // Verificar si hay más páginas
        $this->hasMorePages = $posts->count() > $this->perPage;

        // Agregar nuevos posts al array acumulado
        $newPosts = $posts->take($this->perPage);
        foreach ($newPosts as $post) {
            $this->loadedPosts[] = $post;
        }
    }

    public function getSocialPosts()
    {
        return collect($this->loadedPosts);
    }

    public function loadMore()
    {
        if ($this->hasMorePages) {
            $this->page++;
            $this->loadPosts();
        }
    }

    public function resetPagination()
    {
        $this->page = 1;
        $this->hasMorePages = true;
        $this->loadedPosts = [];
        $this->loadPosts();
    }

    public function refreshPosts()
    {
        // Este método se ejecuta cuando se recibe el evento 'post-created'
        // Resetear paginación y recargar
        $this->resetPagination();
        $this->dispatch('$refresh');
    }

    public function toggleReaction($postId, $reactionType)
    {
        $user = auth()->user();
        if (!$user || !$user->company_id) {
            session()->flash('error', 'Debes tener una empresa asociada para dar reacciones.');
            return;
        }

        // Usar withoutGlobalScope para permitir interacciones cross-tenant
        $post = SocialPost::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->findOrFail($postId);
        $userId = $user->id;
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

        $user = auth()->user();
        if (!$user || !$user->company_id) {
            session()->flash('error', 'Debes tener una empresa asociada para comentar.');
            return;
        }

        // Usar withoutGlobalScope para permitir interacciones cross-tenant
        $post = SocialPost::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->findOrFail($postId);

        $newComment = $post->comments()->create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
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
        $this->resetPagination();
        $this->dispatch('$refresh');
    }

    // Resetear paginación cuando cambian los filtros
    public function updated($propertyName)
    {
        if (in_array($propertyName, ['filterType', 'filterCity', 'filterDateFrom', 'filterDateTo', 'filterSearch'])) {
            $this->resetPagination();
        }
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