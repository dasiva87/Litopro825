<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentType extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relación con documentos
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCode($query, $code)
    {
        return $query->where('code', $code);
    }

    // Métodos de negocio
    public function isQuotationType(): bool
    {
        return $this->code === 'QUOTE';
    }

    public function isOrderType(): bool
    {
        return $this->code === 'ORDER';
    }

    public function isInvoiceType(): bool
    {
        return $this->code === 'INVOICE';
    }

    // Métodos estáticos para obtener tipos específicos
    public static function getQuoteType()
    {
        return self::byCode('QUOTE')->first();
    }

    public static function getOrderType()
    {
        return self::byCode('ORDER')->first();
    }

    public static function getInvoiceType()
    {
        return self::byCode('INVOICE')->first();
    }

    // Constantes para códigos comunes
    const QUOTE = 'QUOTE';
    const ORDER = 'ORDER';
    const INVOICE = 'INVOICE';
    const PAPER = 'PAPER';
    const PURCHASE = 'PURCHASE';
    const DELIVERY = 'DELIVERY';
}