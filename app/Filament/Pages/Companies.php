<?php

namespace App\Filament\Pages;

use App\Models\Company;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;

class Companies extends Page
{
    protected string $view = 'filament.pages.companies';

    protected static ?string $title = 'Empresas';

    protected static ?string $navigationLabel = 'Empresas';

    protected static ?string $slug = 'companies';

    protected static bool $shouldRegisterNavigation = false;

    public $companies = [];
    public $userCompanyId = null;

    public function mount(): void
    {
        $this->userCompanyId = auth()->user()->company_id;

        // Obtener todas las empresas públicas, excluyendo la empresa del usuario actual
        $this->companies = Company::with(['city', 'state', 'country'])
            ->where('is_public', true)
            ->where('is_active', true)
            ->where('id', '!=', $this->userCompanyId)
            ->orderBy('name')
            ->get()
            ->map(function ($company) {
                return [
                    'id' => $company->id,
                    'name' => $company->name,
                    'slug' => $company->slug,
                    'bio' => $company->bio,
                    'avatar' => $company->avatar ? Storage::url($company->avatar) : null,
                    'banner' => $company->banner ? Storage::url($company->banner) : null,
                    'avatar_initials' => $this->getInitials($company->name),
                    'city' => $company->city?->name,
                    'state' => $company->state?->name,
                    'company_type' => $company->company_type?->label() ?? 'N/A',
                    'followers_count' => $company->followers_count ?? 0,
                    'posts_count' => $company->posts_count ?? 0,
                    'is_following' => $this->isFollowing($company->id),
                ];
            })
            ->toArray();
    }

    private function getInitials(string $name): string
    {
        $words = explode(' ', $name);
        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
        }
        return strtoupper(substr($name, 0, 2));
    }

    private function isFollowing(int $companyId): bool
    {
        if (!$this->userCompanyId) {
            return false;
        }

        $userCompany = Company::find($this->userCompanyId);
        return $userCompany ? $userCompany->isFollowing(Company::find($companyId)) : false;
    }

    public function followCompany(int $companyId)
    {
        if (!$this->userCompanyId) {
            session()->flash('error', 'Debes tener una empresa asociada.');
            return;
        }

        $userCompany = Company::find($this->userCompanyId);
        $targetCompany = Company::find($companyId);

        if (!$userCompany || !$targetCompany) {
            session()->flash('error', 'Empresa no encontrada.');
            return;
        }

        if ($userCompany->isFollowing($targetCompany)) {
            $userCompany->unfollow($targetCompany);
            session()->flash('success', 'Has dejado de seguir a ' . $targetCompany->name);
        } else {
            $userCompany->follow($targetCompany);
            session()->flash('success', 'Ahora sigues a ' . $targetCompany->name);
        }

        // Recargar empresas
        $this->mount();
    }

    public function getTitle(): string
    {
        return 'Empresas';
    }

    public function getHeading(): string
    {
        return 'Directorio de Empresas';
    }
}
