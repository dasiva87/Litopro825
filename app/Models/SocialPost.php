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

    public function likes(): HasMany
    {
        return $this->hasMany(SocialLike::class, 'post_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(SocialComment::class, 'post_id');
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
}