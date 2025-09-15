<?php

namespace App\Filament\Widgets;

use App\Models\Company;
use Filament\Widgets\Widget;
use Illuminate\Contracts\View\View;

class SuggestedCompaniesWidget extends Widget
{
    protected string $view = 'filament.widgets.suggested-companies';

    protected static ?int $sort = 6;

    protected int | string | array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    public function getSuggestedCompanies()
    {
        try {
            $user = auth()->user();
            if (!$user || !$user->company) {
                return collect();
            }
            $userCompany = $user->company;

            // Obtener empresas sugeridas basadas en:
            // 1. Misma ciudad
            // 2. No seguidas aún
            // 3. Activas y públicas
            // 4. No sea la propia empresa
            $suggestions = Company::query()
                ->where('id', '!=', $userCompany->id)
                ->where('is_active', true)
                ->where('is_public', true)
                ->whereNotIn('id', function ($query) use ($userCompany) {
                    $query->select('followed_company_id')
                        ->from('company_followers')
                        ->where('follower_company_id', $userCompany->id);
                })
                ->when($userCompany->city_id, function ($query) use ($userCompany) {
                    $query->where('city_id', $userCompany->city_id);
                })
                ->with(['city'])
                ->withCount(['followers'])
                ->orderBy('followers_count', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($company) {
                    return [
                        'id' => $company->id,
                        'name' => $company->name,
                        'slug' => $company->slug,
                        'bio' => $company->bio ? (strlen($company->bio) > 60 ? substr($company->bio, 0, 60) . '...' : $company->bio) : null,
                        'avatar_url' => $company->getAvatarUrl(),
                        'city' => $company->city?->name,
                        'followers_count' => $company->followers_count,
                        'profile_url' => $company->getProfileUrl(),
                        'avatar_initials' => strtoupper(substr($company->name, 0, 2)),
                    ];
                });

            return $suggestions;
        } catch (\Exception $e) {
            \Log::error('SuggestedCompaniesWidget error: ' . $e->getMessage());
            return collect();
        }
    }

    public function followCompany(int $companyId)
    {
        try {
            $user = auth()->user();
            $userCompany = $user->company;
            $company = Company::findOrFail($companyId);

            if ($userCompany->id === $company->id) {
                session()->flash('social-error', 'No puedes seguir tu propia empresa');
                return;
            }

            if ($userCompany->isFollowing($company)) {
                session()->flash('social-info', 'Ya sigues a esta empresa');
                return;
            }

            $userCompany->follow($company, $user);

            // Notificar usando el servicio
            app(\App\Services\NotificationService::class)
                ->notifyNewFollower($company, $userCompany, $user);

            session()->flash('social-success', 'Ahora sigues a ' . $company->name);

            // Refrescar el widget
            $this->dispatch('companies-updated');
        } catch (\Exception $e) {
            \Log::error('Error following company: ' . $e->getMessage());
            session()->flash('social-error', 'Error al seguir la empresa');
        }
    }

    public function getViewData(): array
    {
        return [
            'suggestions' => $this->getSuggestedCompanies(),
        ];
    }
}