<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyFollower;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CompanyFollowController extends Controller
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function toggle(Request $request, Company $company): JsonResponse
    {
        $user = $request->user();
        $userCompany = $user->company;

        // No puedes seguirte a ti mismo
        if ($userCompany->id === $company->id) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes seguir tu propia empresa'
            ], 400);
        }

        // Verificar si ya sigue a la empresa
        $isFollowing = $userCompany->isFollowing($company);

        try {
            if ($isFollowing) {
                // Dejar de seguir
                $userCompany->unfollow($company);

                return response()->json([
                    'success' => true,
                    'action' => 'unfollowed',
                    'message' => 'Has dejado de seguir a ' . $company->name,
                    'is_following' => false,
                    'followers_count' => $company->fresh()->followers_count
                ]);
            } else {
                // Seguir
                $userCompany->follow($company, $user);

                // Enviar notificación
                $this->notificationService->notifyNewFollower($company, $userCompany, $user);

                return response()->json([
                    'success' => true,
                    'action' => 'followed',
                    'message' => 'Ahora sigues a ' . $company->name,
                    'is_following' => true,
                    'followers_count' => $company->fresh()->followers_count
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la solicitud: ' . $e->getMessage()
            ], 500);
        }
    }

    public function check(Request $request, Company $company): JsonResponse
    {
        $user = $request->user();
        $userCompany = $user->company;

        $isFollowing = $userCompany->isFollowing($company);

        return response()->json([
            'success' => true,
            'is_following' => $isFollowing,
            'followers_count' => $company->followers_count,
            'following_count' => $company->following_count
        ]);
    }

    public function suggestions(Request $request): JsonResponse
    {
        $user = $request->user();
        $userCompany = $user->company;

        // Obtener empresas sugeridas basadas en:
        // 1. Misma ciudad
        // 2. No seguidas aún
        // 3. Activas y públicas
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
            ->limit(10)
            ->get()
            ->map(function ($company) {
                return [
                    'id' => $company->id,
                    'name' => $company->name,
                    'slug' => $company->slug,
                    'bio' => $company->bio,
                    'avatar' => $company->getAvatarUrl(),
                    'city' => $company->city?->name,
                    'followers_count' => $company->followers_count,
                    'profile_url' => $company->getProfileUrl(),
                ];
            });

        return response()->json([
            'success' => true,
            'suggestions' => $suggestions
        ]);
    }
}