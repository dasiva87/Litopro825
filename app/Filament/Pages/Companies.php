<?php

namespace App\Filament\Pages;

use App\Enums\CompanyType;
use App\Models\Company;
use App\Models\City;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Url;

class Companies extends Page
{
    protected string $view = 'filament.pages.companies';

    protected static ?string $title = 'Empresas';

    protected static ?string $navigationLabel = 'Empresas';

    protected static ?string $slug = 'companies';

    protected static bool $shouldRegisterNavigation = false;

    public $companies = [];

    public $userCompanyId = null;

    #[Url]
    public string $search = '';

    #[Url]
    public string $filterType = '';

    #[Url]
    public string $filterCity = '';

    public array $availableCities = [];

    public array $availableTypes = [];

    public int $totalCompanies = 0;

    public function mount(): void
    {
        $this->userCompanyId = auth()->user()->company_id;

        // Cargar opciones para filtros
        $this->loadFilterOptions();

        // Cargar empresas
        $this->loadCompanies();
    }

    private function loadFilterOptions(): void
    {
        // Tipos de empresa
        $this->availableTypes = CompanyType::getOptions();

        // Ciudades con empresas activas
        $this->availableCities = Company::where('is_public', true)
            ->where('is_active', true)
            ->where('id', '!=', $this->userCompanyId)
            ->whereNotNull('city_id')
            ->with('city')
            ->get()
            ->pluck('city.name', 'city.id')
            ->filter()
            ->unique()
            ->sort()
            ->toArray();
    }

    public function loadCompanies(): void
    {
        $query = Company::with(['city', 'state', 'country'])
            ->where('is_public', true)
            ->where('is_active', true)
            ->where('id', '!=', $this->userCompanyId);

        // Filtro de búsqueda
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('bio', 'like', '%' . $this->search . '%');
            });
        }

        // Filtro por tipo
        if (!empty($this->filterType)) {
            $query->where('company_type', $this->filterType);
        }

        // Filtro por ciudad
        if (!empty($this->filterCity)) {
            $query->where('city_id', $this->filterCity);
        }

        $this->totalCompanies = $query->count();

        $this->companies = $query
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
                    'company_type_value' => $company->company_type?->value ?? '',
                    'followers_count' => $company->followers_count ?? 0,
                    'posts_count' => $company->posts_count ?? 0,
                    'is_following' => $this->isFollowing($company->id),
                ];
            })
            ->toArray();
    }

    public function updatedSearch(): void
    {
        $this->loadCompanies();
    }

    public function updatedFilterType(): void
    {
        $this->loadCompanies();
    }

    public function updatedFilterCity(): void
    {
        $this->loadCompanies();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->filterType = '';
        $this->filterCity = '';
        $this->loadCompanies();
    }

    private function getInitials(string $name): string
    {
        $words = explode(' ', $name);
        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1).substr($words[1], 0, 1));
        }

        return strtoupper(substr($name, 0, 2));
    }

    private function isFollowing(int $companyId): bool
    {
        if (! $this->userCompanyId) {
            return false;
        }

        $userCompany = Company::find($this->userCompanyId);

        return $userCompany ? $userCompany->isFollowing(Company::find($companyId)) : false;
    }

    public function followCompany(int $companyId)
    {
        if (! $this->userCompanyId) {
            session()->flash('error', 'Debes tener una empresa asociada.');

            return;
        }

        $userCompany = Company::find($this->userCompanyId);
        $targetCompany = Company::find($companyId);

        if (! $userCompany || ! $targetCompany) {
            session()->flash('error', 'Empresa no encontrada.');

            return;
        }

        if ($userCompany->isFollowing($targetCompany)) {
            $userCompany->unfollow($targetCompany);
            session()->flash('success', 'Has dejado de seguir a '.$targetCompany->name);
        } else {
            $userCompany->follow($targetCompany, auth()->user());
            session()->flash('success', 'Ahora sigues a '.$targetCompany->name);
        }

        // Recargar empresas
        $this->loadCompanies();
    }

    public function getTitle(): string
    {
        return 'Directorio de Empresas';
    }

    public function getHeading(): ?string
    {
        return null; // Se maneja en la vista
    }
}
