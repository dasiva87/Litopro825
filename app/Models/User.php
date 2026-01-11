<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Concerns\BelongsToTenant;
use App\Notifications\CustomResetPassword;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Lab404\Impersonate\Models\Impersonate;
use Laravel\Cashier\Billable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use BelongsToTenant, Billable, HasApiTokens, HasFactory, HasRoles, Impersonate, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'email',
        'password',
        'document_type',
        'document_number',
        'phone',
        'mobile',
        'position',
        'address',
        'city_id',
        'state_id',
        'country_id',
        'avatar',
        'is_active',
        'last_login_at',
        'preferences',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'preferences' => 'array',
        ];
    }

    // Relaciones Multi-Tenant (heredadas del trait BelongsToTenant)

    /**
     * Override del método notifications para usar nuestro modelo personalizado
     * que no tiene el TenantScope aplicado.
     */
    public function notifications()
    {
        return $this->morphMany(DatabaseNotification::class, 'notifiable')
            ->latest();
    }

    /**
     * Override del método databaseNotifications que usa Filament
     * para mostrar las notificaciones en el panel.
     */
    public function databaseNotifications()
    {
        return $this->morphMany(DatabaseNotification::class, 'notifiable')
            ->latest();
    }

    // Relaciones geográficas
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    // Métodos de negocio
    public function isAdmin(): bool
    {
        return $this->hasRole(['Super Admin', 'Company Admin']);
    }

    public function canAccessCompany(Company $company): bool
    {
        return $this->company_id === $company->id;
    }

    public function getFullNameAttribute(): string
    {
        return $this->name;
    }

    public function updateLastLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }

    // Métodos de impersonación
    public function canImpersonate(): bool
    {
        return $this->hasRole('Super Admin');
    }

    public function canBeImpersonated(): bool
    {
        return ! $this->hasRole('Super Admin') && $this->is_active;
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new CustomResetPassword($token));
    }
}
