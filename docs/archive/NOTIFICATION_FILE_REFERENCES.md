# GrafiRed 3.0 - Sistema de Notificaciones - REFERENCIAS DE ARCHIVOS

## Ubicación de Todos los Archivos Relacionados

### Modelos (app/Models/)

| Archivo | Líneas | Propósito | Multi-Tenant |
|---------|--------|----------|--------------|
| **SocialNotification.php** | 133 | Notificaciones sociales | ✓ Sí |
| **StockAlert.php** | 306 | Alertas de inventario | ✓ Sí |
| **StockMovement.php** | 161 | Movimientos de stock | ✓ Sí |
| **NotificationChannel.php** | 359 | Canales de entrega | Global |
| **NotificationRule.php** | 10 | Reglas de notificación | Global |
| **NotificationLog.php** | 10 | Logs de envío | Global |
| **StockNotification.php** | 13 | (No implementado aún) | - |

**Archivos Relacionados**:
- `app/Models/Concerns/BelongsToTenant.php` - Trait para aislamiento multi-tenant
- `app/Models/User.php` - Tiene trait `Notifiable`
- `app/Models/Company.php` - Relaciones de seguimiento

---

### Servicios (app/Services/)

| Archivo | Líneas | Responsabilidad |
|---------|--------|-----------------|
| **NotificationService.php** | 219 | Notificaciones sociales |
| **StockNotificationService.php** | 290 | Alertas de stock |

**Métodos Clave NotificationService**:
- `notifyNewPost(SocialPost $post)` - Línea 17-46
- `notifyNewComment(SocialPost $post, $comment, User $commenter)` - Línea 52-75
- `notifyNewReaction(SocialPost $post, string $reactionType, User $reactor)` - Línea 80-125
- `notifyNewFollower(Company $followedCompany, Company $followerCompany, User $follower)` - Línea 130-159
- `getUserNotifications(User $user, int $limit = 10)` - Línea 164-172
- `getUnreadCount(User $user)` - Línea 177-183
- `markAsRead(User $user, array $notificationIds = [])` - Línea 188-200

**Métodos Clave StockNotificationService**:
- `notifyAlert(StockAlert $alert)` - Línea 17-24
- `notifyBatchAlerts(Collection $alerts)` - Línea 29-41
- `sendDailySummary(?int $companyId = null)` - Línea 46-75
- `sendCriticalAlerts(?int $companyId = null)` - Línea 80-107
- `getUsersToNotify(int $companyId, string $severity)` - Línea 112-130
- `notifyDepletionPrediction(int $companyId, array $predictions)` - Línea 152-187
- `getNotificationStats(?int $companyId = null, int $days = 7)` - Línea 205-230
- `cleanupOldNotifications(int $daysToKeep = 30)` - Línea 235-245
- `updateUserNotificationPreferences(User $user, array $preferences)` - Línea 276-289

---

### Clases Notification (app/Notifications/)

| Archivo | Líneas | Tipo | Canales |
|---------|--------|------|---------|
| **StockAlertNotification.php** | 222 | Stock | mail, database |
| **PurchaseOrderCreated.php** | 59 | Orden compra | mail, database |
| **PurchaseOrderStatusChanged.php** | ~50 | Orden compra | mail, database |
| **CollectionAccountSent.php** | ~50 | Cobro | mail, database |
| **CollectionAccountStatusChanged.php** | ~50 | Cobro | mail, database |

**StockAlertNotification Métodos**:
- `via(object $notifiable)` - Línea 33-43
- `toMail(object $notifiable)` - Línea 48-57
- `buildSingleAlertEmail(MailMessage $mailMessage)` - Línea 62-83
- `buildBatchAlertEmail(MailMessage $mailMessage)` - Línea 88-122
- `toDatabase(object $notifiable)` - Línea 127-155
- `toArray(object $notifiable)` - Línea 160-163
- `shouldSendEmail(object $notifiable)` - Línea 168-183
- `getPriority()` - Línea 188-205
- `getDelay()` - Línea 210-220

---

### Eventos (app/Events/)

| Archivo | Líneas | Disparador |
|---------|--------|-----------|
| **DocumentCreated.php** | 23 | Documento nuevo |
| **PurchaseOrderStatusChanged.php** | 26 | Cambio de estado PO |
| **StockUpdated.php** | 25 | Movimiento de stock |

**Estructura Evento**:
```php
class MyEvent {
    use Dispatchable, InteractsWithSockets, SerializesModels;
    
    public function __construct(public Model $model) {}
}

// Dispatchar
MyEvent::dispatch($model);
```

---

### Listeners (app/Listeners/)

| Archivo | Líneas | Escucha |
|---------|--------|---------|
| **NotifyPurchaseOrderStatusChange.php** | 27 | PurchaseOrderStatusChanged |

**Nota**: El listener está vacío, necesita implementación.

---

### Widgets Filament (app/Filament/Widgets/)

| Archivo | Líneas | Propósito |
|---------|--------|----------|
| **CreatePostWidget.php** | ~100 | Crear posts con notificaciones |
| **SocialPostWidget.php** | ~200 | Ver posts y notificaciones |
| **SuggestedCompaniesWidget.php** | ~100 | Sugerencias con notificaciones |
| **PurchaseOrderNotificationsWidget.php** | 108 | Mostrar órdenes pendientes |

**CreatePostWidget**:
- Línea 66: `app(NotificationService::class)->notifyNewPost($post);`

**SocialPostWidget**:
- Línea 117: Notificación de comentario
- Línea 150: Notificación de reacción

**PurchaseOrderNotificationsWidget**:
- Muestra órdenes que requieren atención (enviadas hace > 3 días o entregas próximas)

---

### Controllers (app/Http/Controllers/)

| Archivo | Líneas | Uso |
|---------|--------|-----|
| **CompanyFollowController.php** | 128 | Seguimiento con notificaciones |

**Método notificación**:
- Línea 51: `$this->notificationService->notifyNewFollower($company, $userCompany, $user);`

---

### Pages Filament (app/Filament/Pages/)

| Archivo | Propósito |
|---------|----------|
| **StockManagement.php** | Gestión de stock |

**Línea 52**: Inyección de `StockNotificationService`

---

### Migraciones (database/migrations/)

| Archivo | Tabla | Campos |
|---------|-------|--------|
| **2025_09_13_211725_create_social_notifications_table.php** | social_notifications | 11 |
| **2025_09_18_010805_create_notifications_table.php** | notifications | 6 (Laravel default) |
| **2025_09_22_024403_create_notification_channels_table.php** | notification_channels | 34 |
| **2025_09_22_024421_create_notification_rules_table.php** | notification_rules | 49 |
| **2025_09_22_024443_create_notification_logs_table.php** | notification_logs | 40 |
| **2025_09_23_012216_add_min_stock_to_papers_table.php** | papers | +min_stock |
| **2025_09_23_012604_create_stock_movements_table.php** | stock_movements | 21 |
| **2025_09_23_020738_create_stock_alerts_table.php** | stock_alerts | 27 |
| **2025_09_23_101237_create_stock_notifications_table.php** | stock_notifications | 2 (básica) |

---

### Configuración (config/)

| Archivo | Configuración |
|---------|--------------|
| **queue.php** | QUEUE_CONNECTION=database |
| **mail.php** | MAIL_MAILER=log |
| **services.php** | Integraciones externas |

---

### Providers (app/Providers/)

| Archivo | Línea | Configuración |
|---------|-------|----------------|
| **AppServiceProvider.php** | 32-33 | Singleton StockNotificationService |

```php
$this->app->singleton(StockNotificationService::class, function ($app) {
    return new StockNotificationService();
});
```

---

### Archivos Base de Datos (.env)

```env
# Queue
QUEUE_CONNECTION=database

# Mail
MAIL_MAILER=log
MAIL_SCHEME=null
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

---

## Relaciones Entre Archivos

### Flujo de Creación de Post

```
CreatePostWidget.php
    ├─ Crea SocialPost
    ├─ app(NotificationService::class)
    └─ NotificationService.php::notifyNewPost()
        ├─ Queries con forTenant()
        ├─ Crea SocialNotification
        └─ broadcastNotification()
```

### Flujo de Alerta de Stock

```
StockMovement::create() [en algún controller]
    ├─ StockUpdated::dispatch($movement)
    ├─ Event → Listener (vacío actualmente)
    └─ Manual: StockNotificationService.php::notifyAlert()
        ├─ getUsersToNotify()
        └─ StockAlertNotification::class
            ├─ toMail()
            ├─ toDatabase()
            └─ Queue procesa
```

### Flujo de Orden de Compra

```
PurchaseOrder creada/actualizada
    ├─ PurchaseOrderStatusChanged::dispatch()
    ├─ NotifyPurchaseOrderStatusChange Listener (vacío)
    └─ Manual: PurchaseOrderCreated Notification
        ├─ via(['mail', 'database'])
        └─ Queue procesa
```

---

## Búsqueda Rápida de Código

### Dónde se crea SocialNotification

- `/app/Services/NotificationService.php` línea 30-42
- `/app/Services/NotificationService.php` línea 59-71
- `/app/Services/NotificationService.php` línea 109-121
- `/app/Services/NotificationService.php` línea 140-154

### Dónde se crea StockAlert

- Listener: `app/Listeners/` (vacío, necesita ser creado)
- Manual: En cualquier lugar donde se registre StockMovement

### Dónde se usa NotificationService

- `/app/Filament/Widgets/CreatePostWidget.php` línea 66
- `/app/Filament/Widgets/SocialPostWidget.php` línea 117, 150
- `/app/Filament/Widgets/SuggestedCompaniesWidget.php` línea 92
- `/app/Http/Controllers/Api/CompanyFollowController.php` línea 51

### Dónde se usa StockNotificationService

- `/app/Filament/Pages/StockManagement.php` línea 52

### Dónde se disparan eventos

- Events despachados en: `app/Events/` (3 eventos definidos)
- Listeners en: `app/Listeners/` (1 listener vacío)

---

## Estadísticas de Código

### Total de Líneas
- Modelos: ~1000 líneas
- Servicios: ~500 líneas
- Notificaciones: ~500 líneas
- Eventos: ~75 líneas
- Listeners: ~27 líneas
- Migraciones: ~500 líneas
- **Total**: ~2600 líneas

### Tipos de Notificaciones
- Social: 5 tipos (new_post, post_comment, post_reaction, post_mention, new_follower)
- Stock: 6 tipos (low_stock, out_of_stock, critical_low, reorder_point, excess_stock, movement_anomaly)
- Sistema: 4 tipos (PO created, PO status, Collection sent, Collection status)
- Canales configurables: 8 tipos (email, slack, teams, discord, webhook, sms, push, database)

### Tablas en Base de Datos
- social_notifications: 11 campos
- stock_alerts: 27 campos
- stock_movements: 21 campos
- notifications: 6 campos (Laravel)
- notification_channels: 34 campos
- notification_rules: 49 campos
- notification_logs: 40 campos

---

## Próximos Pasos para Implementación

### 1. Completar Listener
```bash
vi app/Listeners/NotifyPurchaseOrderStatusChange.php
```

Debe llamar a `PurchaseOrderStatusChanged` Notification

### 2. Completar StockNotification Model
```bash
vi app/Models/StockNotification.php
```

Actualmente tiene solo migration, sin implementación

### 3. Crear Resource Filament para NotificationRule
```bash
php artisan make:filament-resource NotificationRule
```

### 4. Crear Resource Filament para NotificationChannel
```bash
php artisan make:filament-resource NotificationChannel
```

### 5. Implementar integraciones externas
- Slack
- Teams
- SMS
- Push

---

## Conclusión

El sistema de notificaciones está bien estructurado y organizado:
- Separación clara de responsabilidades
- Arquitectura modular y extensible
- Aislamiento multi-tenant
- Soporte para procesamiento asíncrono

Los archivos están organizados en directorios estándar de Laravel/Filament, facilitando mantenimiento y escalabilidad.

