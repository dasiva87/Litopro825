<?php

namespace App\Filament\Widgets;

use App\Models\SocialPost;
use App\Services\NotificationService;
use Filament\Widgets\Widget;
use Livewire\Component;
use Livewire\WithFileUploads;

class CreatePostWidget extends Widget
{
    use WithFileUploads;

    protected string $view = 'filament.widgets.create-post-widget';

    protected static ?int $sort = 7;

    protected int | string | array $columnSpan = 'full';

    public $content = '';
    public $post_type = 'news';
    public $title = '';
    public $is_public = true;
    public $image;

    /**
     * Verificar si el widget debe mostrarse
     */
    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->can('create', SocialPost::class);
    }

    public function createPost()
    {
        // Verificar permiso antes de crear el post
        if (!auth()->user()->can('create', SocialPost::class)) {
            session()->flash('error', 'No tienes permiso para crear posts.');
            return;
        }

        if (empty(trim($this->content))) {
            return;
        }

        // Manejar la carga de imagen
        $imagePath = null;
        if ($this->image) {
            $imagePath = $this->image->store('social-posts', 'public');
        }

        $post = SocialPost::create([
            'company_id' => auth()->user()->company_id,
            'user_id' => auth()->id(),
            'post_type' => $this->post_type,
            'title' => $this->title ?: null,
            'content' => trim($this->content),
            'image_path' => $imagePath,
            'is_public' => $this->is_public,
            'is_featured' => false,
            'tags' => []
        ]);

        // Enviar notificaciones a otros usuarios de la empresa
        $notificationService = app(NotificationService::class);
        $notificationService->notifyNewPost($post);

        // Limpiar campos después de publicar
        $this->reset(['content', 'title', 'image']);

        // Emitir evento para actualizar el feed de posts
        $this->dispatch('post-created');

        // Emitir evento para actualizar notificaciones
        $this->dispatch('notifications-updated');

        // Notificación de éxito (opcional)
        session()->flash('message', '¡Post publicado exitosamente!');
    }

    public function removeImage()
    {
        $this->image = null;
    }

    public function updatedImage()
    {
        $this->validate([
            'image' => 'image|max:2048', // Max 2MB
        ]);
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
}