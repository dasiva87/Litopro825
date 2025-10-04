<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SocialPost extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'company_id',
        'user_id',
        'post_type',
        'title',
        'content',
        'image_path',
        'metadata',
        'is_public',
        'is_featured',
        'tags',
        'contact_info',
        'expires_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'tags' => 'array',
        'contact_info' => 'array',
        'is_public' => 'boolean',
        'is_featured' => 'boolean',
        'expires_at' => 'datetime',
    ];

    // Post types
    const TYPE_OFFER = 'offer';           // Oferta de servicios
    const TYPE_REQUEST = 'request';       // Solicitud de servicios
    const TYPE_NEWS = 'news';             // Noticias del sector
    const TYPE_EQUIPMENT = 'equipment';   // Venta/alquiler de equipo
    const TYPE_MATERIALS = 'materials';   // Venta de materiales
    const TYPE_COLLABORATION = 'collaboration'; // Propuesta de colaboración

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(SocialPostReaction::class, 'post_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(SocialPostComment::class, 'post_id');
    }

    public function likes(): HasMany
    {
        return $this->reactions()->where('reaction_type', SocialPostReaction::TYPE_LIKE);
    }

    // Helper methods for reactions count
    public function getLikesCountAttribute(): int
    {
        return $this->reactions()->where('reaction_type', SocialPostReaction::TYPE_LIKE)->count();
    }

    public function getCommentsCountAttribute(): int
    {
        return $this->comments()->count();
    }

    public function getReactionsCounts(): array
    {
        return $this->reactions()
            ->selectRaw('reaction_type, count(*) as count')
            ->groupBy('reaction_type')
            ->pluck('count', 'reaction_type')
            ->toArray();
    }

    public function hasUserReacted($userId, $reactionType = null): bool
    {
        $query = $this->reactions()->where('user_id', $userId);
        if ($reactionType) {
            $query->where('reaction_type', $reactionType);
        }
        return $query->exists();
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('post_type', $type);
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function getPostTypeLabel(): string
    {
        return match($this->post_type) {
            self::TYPE_OFFER => 'Oferta de Servicios',
            self::TYPE_REQUEST => 'Solicitud',
            self::TYPE_NEWS => 'Noticia',
            self::TYPE_EQUIPMENT => 'Equipo',
            self::TYPE_MATERIALS => 'Materiales',
            self::TYPE_COLLABORATION => 'Colaboración',
            default => 'Publicación',
        };
    }

    public function getPostTypeColor(): string
    {
        return match($this->post_type) {
            self::TYPE_OFFER => 'success',
            self::TYPE_REQUEST => 'warning',
            self::TYPE_NEWS => 'info',
            self::TYPE_EQUIPMENT => 'primary',
            self::TYPE_MATERIALS => 'secondary',
            self::TYPE_COLLABORATION => 'purple',
            default => 'gray',
        };
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public static function getPostTypes(): array
    {
        return [
            self::TYPE_OFFER => 'Oferta de Servicios',
            self::TYPE_REQUEST => 'Solicitud de Servicios',
            self::TYPE_NEWS => 'Noticia del Sector',
            self::TYPE_EQUIPMENT => 'Equipo/Maquinaria',
            self::TYPE_MATERIALS => 'Materiales',
            self::TYPE_COLLABORATION => 'Colaboración',
        ];
    }

    public function hasImage(): bool
    {
        return !empty($this->image_path);
    }

    public function getImageUrl(): ?string
    {
        if (!$this->hasImage()) {
            return null;
        }

        return asset('storage/' . $this->image_path);
    }

    // Company-related methods
    public function getCompanyName(): string
    {
        return $this->company?->name ?? 'Empresa Desconocida';
    }

    public function getCompanySlug(): ?string
    {
        return $this->company?->slug;
    }

    public function getCompanyProfileUrl(): ?string
    {
        if (!$this->company?->slug) {
            return null;
        }

        return '/empresa/' . $this->company->slug;
    }

    public function getCompanyAvatarUrl(): ?string
    {
        return $this->company?->getAvatarUrl();
    }

    public function getCompanyInitials(): string
    {
        $companyName = $this->getCompanyName();
        if ($companyName === 'Empresa Desconocida') {
            return '??';
        }

        return strtoupper(substr($companyName, 0, 2));
    }

    public function isFromFollowedCompany(?int $userCompanyId = null): bool
    {
        if (!$userCompanyId) {
            $userCompanyId = auth()->user()?->company_id;
        }

        if (!$userCompanyId || !$this->company_id) {
            return false;
        }

        // No mostrar como "seguida" si es de la misma empresa
        if ($userCompanyId === $this->company_id) {
            return false;
        }

        return \App\Models\CompanyFollower::where('follower_company_id', $userCompanyId)
            ->where('followed_company_id', $this->company_id)
            ->exists();
    }

    public function getCompanyLocation(): ?string
    {
        if (!$this->company) {
            return null;
        }

        $location = collect([
            $this->company->city?->name,
            $this->company->state?->name,
        ])->filter()->implode(', ');

        return $location ?: null;
    }

    public function getCompanyFollowersCount(): int
    {
        return $this->company?->followers_count ?? 0;
    }

    public function canUserEdit(?int $userId = null): bool
    {
        if (!$userId) {
            $userId = auth()->id();
        }

        if (!$userId) {
            return false;
        }

        // El autor del post puede editarlo
        if ($this->user_id === $userId) {
            return true;
        }

        // Los admins de la empresa pueden editar posts de su empresa
        $user = \App\Models\User::find($userId);
        if ($user && $user->company_id === $this->company_id) {
            return $user->hasAnyRole(['Super Admin', 'Company Admin', 'Manager']);
        }

        return false;
    }

    // Scope para posts de empresas seguidas
    public function scopeFromFollowedCompanies($query, ?int $userCompanyId = null)
    {
        if (!$userCompanyId) {
            $userCompanyId = auth()->user()?->company_id;
        }

        if (!$userCompanyId) {
            return $query->whereRaw('1 = 0'); // No results
        }

        return $query->whereIn('company_id', function ($subQuery) use ($userCompanyId) {
            $subQuery->select('followed_company_id')
                ->from('company_followers')
                ->where('follower_company_id', $userCompanyId);
        });
    }

    // Scope para feed personalizado (posts propios + seguidas + destacados)
    public function scopeForFeed($query, ?int $userCompanyId = null)
    {
        if (!$userCompanyId) {
            $userCompanyId = auth()->user()?->company_id;
        }

        if (!$userCompanyId) {
            return $query->public()->notExpired();
        }

        return $query->where(function ($q) use ($userCompanyId) {
            // Posts de la propia empresa
            $q->where('company_id', $userCompanyId)
              // O posts de empresas seguidas
              ->orWhereIn('company_id', function ($subQuery) use ($userCompanyId) {
                  $subQuery->select('followed_company_id')
                      ->from('company_followers')
                      ->where('follower_company_id', $userCompanyId);
              })
              // O posts públicos destacados
              ->orWhere(function ($featuredQuery) {
                  $featuredQuery->where('is_public', true)
                              ->where('is_featured', true);
              });
        })->public()->notExpired();
    }

    // Scope para búsqueda de hashtags
    public function scopeWithHashtag($query, string $hashtag)
    {
        return $query->whereJsonContains('tags', $hashtag);
    }

    // Scope para búsqueda semántica
    public function scopeSearch($query, string $search)
    {
        $searchTerms = explode(' ', strtolower(trim($search)));

        return $query->where(function ($q) use ($searchTerms) {
            foreach ($searchTerms as $term) {
                $q->where(function ($subQuery) use ($term) {
                    $subQuery->where('title', 'LIKE', "%{$term}%")
                           ->orWhere('content', 'LIKE', "%{$term}%")
                           ->orWhereJsonContains('tags', $term)
                           ->orWhereHas('company', function($companyQuery) use ($term) {
                               $companyQuery->where('name', 'LIKE', "%{$term}%");
                           });
                });
            }
        });
    }

    // Extraer hashtags del contenido
    public function extractHashtags(): array
    {
        $content = $this->content ?? '';
        preg_match_all('/#([a-zA-Z0-9_áéíóúñÁÉÍÓÚÑüÜ]+)/', $content, $matches);

        return array_unique(array_map('strtolower', $matches[1] ?? []));
    }

    // Obtener hashtags populares
    public static function getPopularHashtags(int $limit = 10): array
    {
        $allTags = collect();

        static::whereNotNull('tags')
              ->where('is_public', true)
              ->whereDate('created_at', '>=', now()->subDays(30))
              ->get(['tags'])
              ->each(function ($post) use ($allTags) {
                  if (is_array($post->tags)) {
                      $allTags->push(...$post->tags);
                  }
              });

        return $allTags->countBy()
                      ->sortDesc()
                      ->take($limit)
                      ->keys()
                      ->toArray();
    }

    // Formatear contenido con hashtags como enlaces
    public function getFormattedContent(): string
    {
        $content = $this->content ?? '';

        // SECURITY: Escapar HTML primero para prevenir XSS
        $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');

        // Convertir saltos de línea a <br>
        $content = nl2br($content);

        // Convertir hashtags a enlaces (después de escapar)
        $content = preg_replace(
            '/#([a-zA-Z0-9_áéíóúñÁÉÍÓÚÑüÜ]+)/',
            '<span class="hashtag text-blue-600 hover:text-blue-800 cursor-pointer font-medium" data-hashtag="$1">#$1</span>',
            $content
        );

        return $content;
    }

    // Auto-actualizar hashtags al guardar
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($post) {
            $extractedHashtags = $post->extractHashtags();
            $currentTags = is_array($post->tags) ? $post->tags : [];

            // Combinar hashtags extraídos con tags existentes
            $allTags = array_unique(array_merge($currentTags, $extractedHashtags));

            $post->tags = array_values($allTags);
        });
    }
}