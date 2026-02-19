<?php

namespace App\Filament\SuperAdmin\Resources\Subscriptions\Schemas;

use App\Models\CollectionAccount;
use App\Models\Contact;
use App\Models\Document;
use App\Models\Finishing;
use App\Models\Paper;
use App\Models\PrintingMachine;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\User;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SubscriptionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                // Información de la Suscripción
                Section::make('Información de la Suscripción')
                    ->icon('heroicon-o-credit-card')
                    ->columnSpan(2)
                    ->columns(4)
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nombre'),
                        TextEntry::make('stripe_status')
                            ->label('Estado')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'active' => 'success',
                                'trialing' => 'info',
                                'cancelled' => 'danger',
                                'past_due' => 'warning',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'active' => 'Activo',
                                'trialing' => 'En Prueba',
                                'cancelled' => 'Cancelado',
                                'past_due' => 'Pago Pendiente',
                                'unpaid' => 'No Pagado',
                                default => $state,
                            }),
                        TextEntry::make('trial_ends_at')
                            ->label('Fin de Prueba')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('Sin período de prueba'),
                        TextEntry::make('ends_at')
                            ->label('Fecha de Finalización')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('Sin fecha de fin'),
                        TextEntry::make('created_at')
                            ->label('Fecha de Creación')
                            ->dateTime('d/m/Y H:i'),
                        TextEntry::make('user.name')
                            ->label('Usuario Responsable'),
                        TextEntry::make('user.email')
                            ->label('Email Responsable'),
                        TextEntry::make('quantity')
                            ->label('Cantidad'),
                    ]),

                // Información de la Empresa
                Section::make('Información de la Empresa')
                    ->icon('heroicon-o-building-office')
                    ->columnSpan(2)
                    ->columns(4)
                    ->schema([
                        TextEntry::make('company.name')
                            ->label('Nombre de la Empresa')
                            ->weight('bold'),
                        TextEntry::make('company.email')
                            ->label('Email')
                            ->copyable(),
                        TextEntry::make('company.phone')
                            ->label('Teléfono')
                            ->placeholder('No registrado'),
                        TextEntry::make('company.company_type')
                            ->label('Tipo de Empresa')
                            ->badge()
                            ->formatStateUsing(fn ($state) => $state?->label() ?? 'No definido')
                            ->color(fn ($state) => match ($state?->value ?? null) {
                                'litografia' => 'primary',
                                'papeleria' => 'success',
                                default => 'gray',
                            }),
                        TextEntry::make('company.status')
                            ->label('Estado de Empresa')
                            ->badge()
                            ->color(fn ($state) => match ($state) {
                                'active' => 'success',
                                'trial' => 'info',
                                'suspended' => 'warning',
                                'cancelled' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn ($state) => match ($state) {
                                'active' => 'Activo',
                                'trial' => 'Prueba',
                                'suspended' => 'Suspendido',
                                'cancelled' => 'Cancelado',
                                'pending' => 'Pendiente',
                                default => $state ?? 'Desconocido',
                            }),
                        TextEntry::make('company.subscription_plan')
                            ->label('Plan')
                            ->badge()
                            ->color('info'),
                        TextEntry::make('company.subscription_expires_at')
                            ->label('Expiración del Plan')
                            ->dateTime('d/m/Y')
                            ->placeholder('Sin expiración'),
                        TextEntry::make('company.max_users')
                            ->label('Máx. Usuarios'),
                        TextEntry::make('company.address')
                            ->label('Dirección')
                            ->columnSpan(2)
                            ->placeholder('No registrada'),
                        TextEntry::make('company.city.name')
                            ->label('Ciudad')
                            ->placeholder('No registrada'),
                        TextEntry::make('company.created_at')
                            ->label('Fecha de Registro')
                            ->dateTime('d/m/Y H:i'),
                    ]),

                // Estadísticas de Uso - Sección Principal
                Section::make('Estadísticas de Uso')
                    ->icon('heroicon-o-chart-bar')
                    ->description('Resumen de la actividad de la empresa')
                    ->columnSpan(2)
                    ->columns(4)
                    ->schema([
                        TextEntry::make('users_count')
                            ->label('Usuarios')
                            ->icon('heroicon-o-users')
                            ->state(fn ($record) => User::where('company_id', $record->company_id)->count())
                            ->badge()
                            ->color('primary'),

                        TextEntry::make('contacts_count')
                            ->label('Contactos/Clientes')
                            ->icon('heroicon-o-user-group')
                            ->state(fn ($record) => Contact::withoutGlobalScopes()
                                ->where('company_id', $record->company_id)->count())
                            ->badge()
                            ->color('success'),

                        TextEntry::make('suppliers_count')
                            ->label('Proveedores')
                            ->icon('heroicon-o-building-storefront')
                            ->state(fn ($record) => Contact::withoutGlobalScopes()
                                ->where('company_id', $record->company_id)
                                ->whereIn('type', ['supplier', 'both'])
                                ->count())
                            ->badge()
                            ->color('warning'),

                        TextEntry::make('projects_count')
                            ->label('Proyectos')
                            ->icon('heroicon-o-folder')
                            ->state(fn ($record) => Project::withoutGlobalScopes()
                                ->where('company_id', $record->company_id)->count())
                            ->badge()
                            ->color('info'),
                    ]),

                // Documentos y Órdenes
                Section::make('Documentos y Órdenes')
                    ->icon('heroicon-o-document-text')
                    ->columnSpan(2)
                    ->columns(4)
                    ->collapsible()
                    ->schema([
                        TextEntry::make('quotations_count')
                            ->label('Cotizaciones')
                            ->icon('heroicon-o-document-text')
                            ->state(fn ($record) => Document::withoutGlobalScopes()
                                ->where('company_id', $record->company_id)->count())
                            ->badge()
                            ->color('primary'),

                        TextEntry::make('purchase_orders_count')
                            ->label('Órdenes de Pedido')
                            ->icon('heroicon-o-shopping-cart')
                            ->state(fn ($record) => PurchaseOrder::withoutGlobalScopes()
                                ->where('company_id', $record->company_id)->count())
                            ->badge()
                            ->color('info'),

                        TextEntry::make('production_orders_count')
                            ->label('Órdenes de Producción')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->state(fn ($record) => ProductionOrder::withoutGlobalScopes()
                                ->where('company_id', $record->company_id)->count())
                            ->badge()
                            ->color('warning'),

                        TextEntry::make('collection_accounts_count')
                            ->label('Cuentas de Cobro')
                            ->icon('heroicon-o-banknotes')
                            ->state(fn ($record) => CollectionAccount::withoutGlobalScopes()
                                ->where('company_id', $record->company_id)->count())
                            ->badge()
                            ->color('success'),

                        TextEntry::make('active_quotations')
                            ->label('Cotizaciones Activas')
                            ->state(fn ($record) => Document::withoutGlobalScopes()
                                ->where('company_id', $record->company_id)
                                ->whereIn('status', ['draft', 'sent', 'in_progress'])
                                ->count())
                            ->badge()
                            ->color('primary'),

                        TextEntry::make('pending_orders')
                            ->label('Pedidos Pendientes')
                            ->state(fn ($record) => PurchaseOrder::withoutGlobalScopes()
                                ->where('company_id', $record->company_id)
                                ->whereIn('status', ['draft', 'sent', 'in_progress'])
                                ->count())
                            ->badge()
                            ->color('warning'),

                        TextEntry::make('production_in_progress')
                            ->label('Producción en Proceso')
                            ->state(fn ($record) => ProductionOrder::withoutGlobalScopes()
                                ->where('company_id', $record->company_id)
                                ->where('status', 'in_progress')
                                ->count())
                            ->badge()
                            ->color('info'),

                        TextEntry::make('pending_collections')
                            ->label('Cobros Pendientes')
                            ->state(fn ($record) => CollectionAccount::withoutGlobalScopes()
                                ->where('company_id', $record->company_id)
                                ->whereNotIn('status', ['paid', 'cancelled'])
                                ->count())
                            ->badge()
                            ->color('danger'),
                    ]),

                // Recursos e Inventario
                Section::make('Recursos e Inventario')
                    ->icon('heroicon-o-cube')
                    ->columnSpan(2)
                    ->columns(4)
                    ->collapsible()
                    ->schema([
                        TextEntry::make('papers_count')
                            ->label('Papeles')
                            ->icon('heroicon-o-document')
                            ->state(fn ($record) => Paper::withoutGlobalScopes()
                                ->where('company_id', $record->company_id)->count())
                            ->badge()
                            ->color('primary'),

                        TextEntry::make('machines_count')
                            ->label('Máquinas')
                            ->icon('heroicon-o-cpu-chip')
                            ->state(fn ($record) => PrintingMachine::withoutGlobalScopes()
                                ->where('company_id', $record->company_id)->count())
                            ->badge()
                            ->color('info'),

                        TextEntry::make('products_count')
                            ->label('Productos')
                            ->icon('heroicon-o-cube')
                            ->state(fn ($record) => Product::withoutGlobalScopes()
                                ->where('company_id', $record->company_id)->count())
                            ->badge()
                            ->color('success'),

                        TextEntry::make('finishings_count')
                            ->label('Acabados')
                            ->icon('heroicon-o-paint-brush')
                            ->state(fn ($record) => Finishing::withoutGlobalScopes()
                                ->where('company_id', $record->company_id)->count())
                            ->badge()
                            ->color('warning'),
                    ]),

                // Métricas Financieras Estimadas
                Section::make('Métricas Financieras')
                    ->icon('heroicon-o-currency-dollar')
                    ->columnSpan(2)
                    ->columns(3)
                    ->collapsible()
                    ->schema([
                        TextEntry::make('total_quoted')
                            ->label('Total Cotizado')
                            ->state(function ($record) {
                                $total = Document::withoutGlobalScopes()
                                    ->where('company_id', $record->company_id)
                                    ->sum('total');
                                return '$' . number_format($total, 0, ',', '.');
                            })
                            ->color('primary'),

                        TextEntry::make('total_orders')
                            ->label('Total en Pedidos')
                            ->state(function ($record) {
                                $total = PurchaseOrder::withoutGlobalScopes()
                                    ->where('company_id', $record->company_id)
                                    ->sum('total_amount');
                                return '$' . number_format($total, 0, ',', '.');
                            })
                            ->color('success'),

                        TextEntry::make('total_collections')
                            ->label('Total en Cuentas de Cobro')
                            ->state(function ($record) {
                                $total = CollectionAccount::withoutGlobalScopes()
                                    ->where('company_id', $record->company_id)
                                    ->sum('total_amount');
                                return '$' . number_format($total, 0, ',', '.');
                            })
                            ->color('info'),

                        TextEntry::make('paid_collections')
                            ->label('Total Cobrado')
                            ->state(function ($record) {
                                $total = CollectionAccount::withoutGlobalScopes()
                                    ->where('company_id', $record->company_id)
                                    ->where('status', 'paid')
                                    ->sum('total_amount');
                                return '$' . number_format($total, 0, ',', '.');
                            })
                            ->color('success'),

                        TextEntry::make('pending_to_collect')
                            ->label('Pendiente por Cobrar')
                            ->state(function ($record) {
                                $total = CollectionAccount::withoutGlobalScopes()
                                    ->where('company_id', $record->company_id)
                                    ->whereNotIn('status', ['paid', 'cancelled'])
                                    ->sum('total_amount');
                                return '$' . number_format($total, 0, ',', '.');
                            })
                            ->color('danger'),

                        TextEntry::make('conversion_rate')
                            ->label('Tasa de Conversión')
                            ->state(function ($record) {
                                $quotations = Document::withoutGlobalScopes()
                                    ->where('company_id', $record->company_id)
                                    ->count();
                                $orders = PurchaseOrder::withoutGlobalScopes()
                                    ->where('company_id', $record->company_id)
                                    ->count();

                                if ($quotations === 0) {
                                    return '0%';
                                }

                                return round(($orders / $quotations) * 100, 1) . '%';
                            })
                            ->helperText('Pedidos / Cotizaciones')
                            ->color('info'),
                    ]),

                // Actividad Reciente
                Section::make('Actividad Reciente')
                    ->icon('heroicon-o-clock')
                    ->columnSpan(2)
                    ->columns(4)
                    ->collapsible()
                    ->schema([
                        TextEntry::make('last_quotation')
                            ->label('Última Cotización')
                            ->state(function ($record) {
                                $doc = Document::withoutGlobalScopes()
                                    ->where('company_id', $record->company_id)
                                    ->latest()
                                    ->first();
                                return $doc ? $doc->created_at->diffForHumans() : 'Sin cotizaciones';
                            }),

                        TextEntry::make('last_order')
                            ->label('Último Pedido')
                            ->state(function ($record) {
                                $order = PurchaseOrder::withoutGlobalScopes()
                                    ->where('company_id', $record->company_id)
                                    ->latest()
                                    ->first();
                                return $order ? $order->created_at->diffForHumans() : 'Sin pedidos';
                            }),

                        TextEntry::make('last_production')
                            ->label('Última Producción')
                            ->state(function ($record) {
                                $prod = ProductionOrder::withoutGlobalScopes()
                                    ->where('company_id', $record->company_id)
                                    ->latest()
                                    ->first();
                                return $prod ? $prod->created_at->diffForHumans() : 'Sin producción';
                            }),

                        TextEntry::make('last_login')
                            ->label('Último Login')
                            ->state(function ($record) {
                                $user = User::where('company_id', $record->company_id)
                                    ->whereNotNull('last_login_at')
                                    ->orderBy('last_login_at', 'desc')
                                    ->first();

                                if (!$user || !$user->last_login_at) {
                                    return 'Sin registro';
                                }

                                return $user->last_login_at->diffForHumans();
                            }),

                        TextEntry::make('quotations_this_month')
                            ->label('Cotizaciones (este mes)')
                            ->state(fn ($record) => Document::withoutGlobalScopes()
                                ->where('company_id', $record->company_id)
                                ->whereMonth('created_at', now()->month)
                                ->whereYear('created_at', now()->year)
                                ->count())
                            ->badge()
                            ->color('primary'),

                        TextEntry::make('orders_this_month')
                            ->label('Pedidos (este mes)')
                            ->state(fn ($record) => PurchaseOrder::withoutGlobalScopes()
                                ->where('company_id', $record->company_id)
                                ->whereMonth('created_at', now()->month)
                                ->whereYear('created_at', now()->year)
                                ->count())
                            ->badge()
                            ->color('success'),

                        TextEntry::make('production_this_month')
                            ->label('Producciones (este mes)')
                            ->state(fn ($record) => ProductionOrder::withoutGlobalScopes()
                                ->where('company_id', $record->company_id)
                                ->whereMonth('created_at', now()->month)
                                ->whereYear('created_at', now()->year)
                                ->count())
                            ->badge()
                            ->color('info'),

                        TextEntry::make('collections_this_month')
                            ->label('Cobros (este mes)')
                            ->state(fn ($record) => CollectionAccount::withoutGlobalScopes()
                                ->where('company_id', $record->company_id)
                                ->whereMonth('created_at', now()->month)
                                ->whereYear('created_at', now()->year)
                                ->count())
                            ->badge()
                            ->color('warning'),
                    ]),
            ]);
    }
}
