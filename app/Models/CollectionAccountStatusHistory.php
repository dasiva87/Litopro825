<?php

namespace App\Models;

use App\Enums\CollectionAccountStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollectionAccountStatusHistory extends Model
{
    protected $fillable = [
        'collection_account_id',
        'from_status',
        'to_status',
        'user_id',
        'notes',
    ];

    protected $casts = [
        'from_status' => CollectionAccountStatus::class,
        'to_status' => CollectionAccountStatus::class,
    ];

    public function collectionAccount(): BelongsTo
    {
        return $this->belongsTo(CollectionAccount::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
