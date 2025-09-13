<?php

namespace App\Filament\Widgets;

use App\Models\SocialPost;
use Filament\Widgets\Widget;
use Livewire\Component;

class CreatePostWidget extends Widget
{
    protected string $view = 'filament.widgets.create-post-widget';

    protected int | string | array $columnSpan = 'full';

    public $content = '';
    public $post_type = 'news';
    public $title = '';
    public $is_public = true;

    public function createPost()
    {
        if (empty(trim($this->content))) {
            return;
        }

        SocialPost::create([
            'company_id' => auth()->user()->company_id,
            'user_id' => auth()->id(),
            'post_type' => $this->post_type,
            'title' => $this->title ?: null,
            'content' => trim($this->content),
            'is_public' => $this->is_public,
            'is_featured' => false,
            'tags' => []
        ]);

        // Limpiar campos después de publicar
        $this->reset(['content', 'title']);

        // Emitir evento para actualizar el feed de posts
        $this->dispatch('post-created');

        // Notificación de éxito (opcional)
        session()->flash('message', '¡Post publicado exitosamente!');
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