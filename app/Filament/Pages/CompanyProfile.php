<?php

namespace App\Filament\Pages;

use App\Models\Company;
use App\Models\SocialPost;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Route;

class CompanyProfile extends Page
{
    protected string $view = 'filament.pages.company-profile';

    protected static bool $shouldRegisterNavigation = false;

    public Company $company;
    public $isFollowing = false;
    public $stats = [];

    public function mount(): void
    {
        // Obtener el slug de la URL
        $slug = request()->route('slug') ?? request()->segment(3);

        // Buscar empresa por slug
        $this->company = Company::where('slug', $slug)
            ->with(['city', 'state', 'country'])
            ->firstOrFail();

        // Verificar si el perfil es público o el usuario tiene acceso
        if (!$this->company->is_public && !auth()->check()) {
            abort(403, 'Este perfil es privado');
        }

        // Verificar si el usuario actual sigue a esta empresa
        if (auth()->check()) {
            $userCompany = auth()->user()->company;
            $this->isFollowing = $userCompany->isFollowing($this->company);
        }

        // Estadísticas de la empresa
        $this->stats = [
            'posts_count' => $this->company->posts_count ?? 0,
            'followers_count' => $this->company->getFollowersCount(),
            'following_count' => $this->company->getFollowingCount(),
        ];
    }

    // Método para obtener posts (computed property)
    public function getPostsProperty()
    {
        return SocialPost::with(['author.company', 'reactions', 'comments.author'])
            ->whereHas('author', function ($query) {
                $query->where('company_id', $this->company->id);
            })
            ->public()
            ->notExpired()
            ->recent()
            ->paginate(10);
    }

    public function getTitle(): string
    {
        return $this->company->name ?? 'Perfil de Empresa';
    }

    public function getHeading(): string
    {
        return $this->company->name ?? 'Perfil de Empresa';
    }

    public static function getSlug(?\Filament\Panel $panel = null): string
    {
        return 'empresa/{slug}';
    }
}
