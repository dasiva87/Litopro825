<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyFollower;
use App\Models\SocialPost;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanyProfileController extends Controller
{
    public function show(string $slug): View
    {
        // Buscar empresa por slug
        $company = Company::where('slug', $slug)
            ->with(['city', 'state', 'country'])
            ->firstOrFail();

        // Verificar si el perfil es público o el usuario tiene acceso
        if (!$company->is_public && !auth()->check()) {
            abort(403, 'Este perfil es privado');
        }

        // Obtener posts de la empresa
        $posts = SocialPost::with(['author', 'reactions', 'comments.author'])
            ->whereHas('author', function ($query) use ($company) {
                $query->where('company_id', $company->id);
            })
            ->public()
            ->notExpired()
            ->recent()
            ->paginate(10);

        // Verificar si el usuario actual sigue a esta empresa
        $isFollowing = false;
        if (auth()->check()) {
            $userCompany = auth()->user()->company;
            $isFollowing = $userCompany->isFollowing($company);
        }

        // Estadísticas de la empresa (actualizar desde BD)
        $stats = [
            'posts_count' => $company->posts_count ?? 0,
            'followers_count' => $company->getFollowersCount(),
            'following_count' => $company->getFollowingCount(),
        ];

        return view('company-profile.show', compact('company', 'posts', 'isFollowing', 'stats'));
    }

    public function followers(string $slug): View
    {
        $company = Company::where('slug', $slug)->firstOrFail();

        // Obtener lista de empresas que siguen a esta empresa
        $followers = CompanyFollower::where('followed_company_id', $company->id)
            ->with(['followerCompany.city'])
            ->recent()
            ->paginate(20)
            ->through(function ($follow) {
                return $follow->followerCompany;
            });

        return view('company-profile.followers', compact('company', 'followers'));
    }

    public function following(string $slug): View
    {
        $company = Company::where('slug', $slug)->firstOrFail();

        // Obtener lista de empresas que esta empresa sigue
        $following = CompanyFollower::where('follower_company_id', $company->id)
            ->with(['followedCompany.city'])
            ->recent()
            ->paginate(20)
            ->through(function ($follow) {
                return $follow->followedCompany;
            });

        return view('company-profile.following', compact('company', 'following'));
    }
}