<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DashboardWidget extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'company_id',
        'user_id',
        'widget_type',
        'widget_key',
        'title',
        'configuration',
        'position_column',
        'position_order',
        'is_active',
        'is_visible',
    ];

    protected $casts = [
        'configuration' => 'array',
        'is_active' => 'boolean',
        'is_visible' => 'boolean',
    ];

    // Widget types constants
    const TYPE_STATS = 'stats';
    const TYPE_QUICK_ACTIONS = 'quick_actions';
    const TYPE_SOCIAL_FEED = 'social_feed';
    const TYPE_PAPER_CALCULATOR = 'paper_calculator';
    const TYPE_STOCK_ALERTS = 'stock_alerts';
    const TYPE_MARKETPLACE = 'marketplace';
    const TYPE_DEADLINES = 'deadlines';
    const TYPE_DOCUMENTS_TABLE = 'documents_table';

    // Column positions
    const COLUMN_CENTER = 'center';
    const COLUMN_RIGHT = 'right';

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    public function scopeForColumn($query, string $column)
    {
        return $query->where('position_column', $column)
                    ->orderBy('position_order');
    }

    public static function getAvailableWidgets(): array
    {
        return [
            self::TYPE_STATS => 'Estadísticas del Negocio',
            self::TYPE_QUICK_ACTIONS => 'Acciones Rápidas',
            self::TYPE_SOCIAL_FEED => 'Red Social de Litografías',
            self::TYPE_PAPER_CALCULATOR => 'Calculadora de Papel',
            self::TYPE_STOCK_ALERTS => 'Alertas de Stock',
            self::TYPE_MARKETPLACE => 'Marketplace de Proveedores',
            self::TYPE_DEADLINES => 'Próximos Vencimientos',
            self::TYPE_DOCUMENTS_TABLE => 'Documentos Activos',
        ];
    }
}