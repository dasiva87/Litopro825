<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialPostReaction extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'company_id',
        'post_id',
        'user_id',
        'reaction_type',
    ];

    // Reaction types
    const TYPE_LIKE = 'like';
    const TYPE_INTERESTED = 'interested';
    const TYPE_HELPFUL = 'helpful';
    const TYPE_CONTACT_ME = 'contact_me';

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(SocialPost::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function getReactionTypes(): array
    {
        return [
            self::TYPE_LIKE => '👍 Me gusta',
            self::TYPE_INTERESTED => '💡 Me interesa',
            self::TYPE_HELPFUL => '✅ Útil',
            self::TYPE_CONTACT_ME => '📞 Contactar',
        ];
    }
}