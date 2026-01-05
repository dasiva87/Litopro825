# Sistema de Notificaciones GrafiRed 3.0 - Análisis Exhaustivo

## Tabla de Contenidos
1. Descripción General
2. Arquitectura del Sistema
3. Modelos de Datos
4. Servicios de Notificaciones
5. Estructuras de Tablas
6. Ejemplos de Código
7. Flujos de Envío/Recepción
8. Configuración Multi-Tenant
9. Casos de Uso Implementados

---

## 1. Descripción General

El sistema de notificaciones de GrafiRed 3.0 es un sistema multi-propósito que maneja **tres tipos principales de notificaciones**:

### 1.1 Tipos de Notificaciones

| Tipo | Modelo | Propósito | Multi-Tenant | Scope |
|------|--------|----------|--------------|-------|
| **Social** | `SocialNotification` | Interacción social (posts, comentarios, reacciones) | ✓ Sí | `forTenant()` |
| **Stock** | `StockAlert` + `StockNotification` | Alertas de inventario | ✓ Sí | `forTenant()` |
| **Sistema** | `Notification` (Laravel) | Órdenes de compra, documentos | ✓ Sí | Automático |
| **Configurables** | `NotificationChannel`, `NotificationRule`, `NotificationLog` | Sistema avanzado de notificaciones | Sí | Global (Super Admin) |

---

## 2. Arquitectura del Sistema

```
┌─────────────────────────────────────────────────────────────┐
│                      CAPA DE INTERFAZ                       │
│  Filament Resources, Widgets, Pages                         │
└─────────────────────┬───────────────────────────────────────┘
                      │
┌─────────────────────▼───────────────────────────────────────┐
│                   CAPA DE EVENTOS                           │
│  - PurchaseOrderStatusChanged                               │
│  - DocumentCreated                                          │
│  - StockUpdated                                             │
│  - Listeners (NotifyPurchaseOrderStatusChange)              │
└─────────────────────┬───────────────────────────────────────┘
                      │
┌─────────────────────▼───────────────────────────────────────┐
│                CAPA DE SERVICIOS                            │
│  - NotificationService (Notif. Sociales)                    │
│  - StockNotificationService (Alertas Stock)                 │
└─────────────────────┬───────────────────────────────────────┘
                      │
┌─────────────────────▼───────────────────────────────────────┐
│              CAPA DE NOTIFICACIONES                         │
│  - StockAlertNotification                                   │
│  - PurchaseOrderCreated                                     │
│  - PurchaseOrderStatusChanged                               │
│  - CollectionAccountSent                                    │
│  - CollectionAccountStatusChanged                           │
└─────────────────────┬───────────────────────────────────────┘
                      │
┌─────────────────────▼───────────────────────────────────────┐
│            CAPA DE PERSISTENCIA                             │
│  - social_notifications (Notif. Sociales)                   │
│  - stock_alerts (Alertas Stock)                             │
│  - stock_movements (Movimientos Stock)                       │
│  - notifications (Laravel DB Notifications)                 │
│  - notification_channels (Canales configurables)            │
│  - notification_rules (Reglas configurables)                │
│  - notification_logs (Logs de envío)                        │
└─────────────────────┬───────────────────────────────────────┘
                      │
┌─────────────────────▼───────────────────────────────────────┐
│            CANALES DE ENTREGA                               │
│  - Mail (SMTP)                                              │
│  - Database (dashboard)                                     │
│  - Queue (procesamiento asíncrono)                          │
│  - Email (futuro: Slack, Teams, Discord, SMS)               │
└─────────────────────────────────────────────────────────────┘
```

---

## 3. Modelos de Datos

### 3.1 SocialNotification (Notificaciones Sociales)

**Archivo**: `/app/Models/SocialNotification.php`

```php
class SocialNotification extends Model
{
    // Traits
    use BelongsToTenant, SoftDeletes;
    
    // Campos
    protected $fillable = [
        'company_id',      // Empresa receptora
        'user_id',         // Usuario que recibe la notificación
        'sender_id',       // Usuario que genera la acción
        'type',            // Tipo de notificación
        'title',           // Título
        'message',         // Mensaje
        'data',            // JSON con datos adicionales
        'read_at',         // Timestamp de lectura
    ];
    
    // Tipos de notificación
    const TYPE_NEW_POST = 'new_post';
    const TYPE_POST_COMMENT = 'post_comment';
    const TYPE_POST_REACTION = 'post_reaction';
    const TYPE_POST_MENTION = 'post_mention';
    const TYPE_NEW_FOLLOWER = 'new_follower';
}
```

**Relaciones**:
- `user()`: Usuario que recibe la notificación
- `sender()`: Usuario que genera la acción
- `company()`: Empresa propietaria

**Scopes**:
- `unread()`: Notificaciones no leídas
- `read()`: Notificaciones leídas
- `recent()`: Ordenadas por fecha descendente
- `byType($type)`: Filtradas por tipo

---

### 3.2 StockAlert (Alertas de Inventario)

**Archivo**: `/app/Models/StockAlert.php`

```php
class StockAlert extends Model
{
    use HasFactory, BelongsToTenant;
    
    protected $fillable = [
        'company_id',           // Empresa
        'stockable_type',       // Tipo (Product, Paper, etc.)
        'stockable_id',         // ID del item
        'type',                 // Tipo de alerta
        'severity',             // Severidad (low, medium, high, critical)
        'status',               // Estado (active, acknowledged, resolved, dismissed)
        'current_stock',        // Stock actual al momento de la alerta
        'min_stock',            // Stock mínimo
        'threshold_value',      // Valor del umbral que disparó la alerta
        'title',                // Título
        'message',              // Mensaje
        'metadata',             // JSON con datos adicionales
        'triggered_at',         // Cuándo se disparó
        'acknowledged_at',      // Cuándo se reconoció
        'resolved_at',          // Cuándo se resolvió
        'acknowledged_by',      // Usuario que reconoció
        'resolved_by',          // Usuario que resolvió
        'auto_resolvable',      // ¿Se puede resolver automáticamente?
        'expires_at',           // Expiración automática
    ];
}
```

**Tipos de Alertas**:
- `low_stock`: Stock bajo
- `out_of_stock`: Sin stock
- `critical_low`: Crítico (menos del 20% del mínimo)
- `reorder_point`: Punto de reorden
- `excess_stock`: Exceso de stock
- `movement_anomaly`: Movimiento anómalo

**Métodos Importantes**:
- `acknowledge()`: Marca como reconocida
- `resolve()`: Marca como resuelta
- `dismiss()`: Descarta la alerta
- `shouldAutoResolve()`: Determina si debe resolverse automáticamente
- `isCritical()`: Verifica si es crítica

---

### 3.3 StockMovement (Movimientos de Inventario)

**Archivo**: `/app/Models/StockMovement.php`

Registra cada movimiento de stock (entrada, salida, ajuste) con trazabilidad completa.

```php
protected $fillable = [
    'company_id',           // Empresa
    'stockable_type',       // Tipo de item (polymorphic)
    'stockable_id',         // ID del item
    'type',                 // in, out, adjustment
    'reason',               // Razón del movimiento
    'quantity',             // Cantidad
    'previous_stock',       // Stock anterior
    'new_stock',            // Stock nuevo
    'unit_cost',            // Costo unitario
    'total_cost',           // Costo total
    'reference_type',       // Tipo de documento de referencia
    'reference_id',         // ID del documento
    'batch_number',         // Número de lote
    'notes',                // Notas
    'metadata',             // JSON adicional
    'user_id',              // Usuario que realizó el movimiento
];
```

---

### 3.4 NotificationChannel (Canales Configurables)

**Archivo**: `/app/Models/NotificationChannel.php`

Sistema flexible para configurar canales de entrega de notificaciones.

```php
protected $fillable = [
    'name',                    // Nombre del canal
    'description',             // Descripción
    'type',                    // email, slack, teams, discord, webhook, sms, push, database
    'status',                  // active, inactive, testing
    'config',                  // JSON con configuración específica
    'rate_limits',             // JSON con límites por minuto/hora/día
    'retry_settings',          // JSON con configuración de reintentos
    'default_template',        // Template por defecto
    'format_settings',         // JSON con configuración de formato
    'filters',                 // JSON con filtros
    'business_hours',          // JSON con horarios de negocio
    'allowed_event_types',     // JSON con tipos de eventos permitidos
    'priority',                // 1=alta, 2=media, 3=baja
    'supports_realtime',       // ¿Soporta tiempo real?
    'supports_bulk',           // ¿Soporta envío en lote?
    'total_sent',              // Total enviado
    'total_delivered',         // Total entregado
    'total_failed',            // Total fallido
    'last_used_at',            // Último uso
    'last_error',              // Último error
    'created_by',              // Usuario que lo creó
];
```

**Tipos de Canales Disponibles**:
- `email`: Correo electrónico (SMTP)
- `slack`: Integración con Slack
- `teams`: Integración con Microsoft Teams
- `discord`: Integración con Discord
- `webhook`: Webhook personalizado
- `sms`: SMS (Twilio)
- `push`: Push notifications (FCM)
- `database`: Notificaciones en BD

---

### 3.5 NotificationRule (Reglas de Notificación)

**Archivo**: `/app/Models/NotificationRule.php`

Sistema avanzado para automatizar el envío de notificaciones basado en eventos y condiciones.

Campos principales:
- `event_type`: Tipo de evento que dispara la regla
- `conditions`: JSON con condiciones que deben cumplirse
- `filters`: Filtros adicionales
- `recipients`: Lista de destinatarios
- `recipient_type`: static, dynamic, role_based
- `channels`: IDs de canales a usar
- `delivery_timing`: immediate, delayed, scheduled, business_hours
- `rate_limit_enabled`: ¿Aplicar límites de frecuencia?
- `priority`: low, normal, high, critical
- `require_acknowledgment`: ¿Requiere confirmación?

---

### 3.6 NotificationLog (Log de Notificaciones)

**Archivo**: `/app/Models/NotificationLog.php`

Registro exhaustivo de cada notificación enviada.

```php
protected $fillable = [
    'notification_rule_id',     // Regla que disparó
    'notification_channel_id',  // Canal usado
    'event_type',               // Tipo de evento
    'event_id',                 // ID del objeto que disparó
    'event_data',               // JSON con datos del evento
    'recipient_type',           // email, user_id, slack_channel, etc.
    'recipient_identifier',     // email, user ID, etc.
    'recipient_name',           // Nombre del destinatario
    'subject',                  // Asunto
    'message',                  // Mensaje
    'status',                   // pending, sent, delivered, failed, bounced, opened, clicked
    'sent_at',                  // Cuándo se envió
    'delivered_at',             // Cuándo se entregó
    'opened_at',                // Cuándo se abrió
    'clicked_at',               // Cuándo se hizo clic
    'error_message',            // Mensaje de error
    'retry_count',              // Número de reintentos
    'channel_type',             // Tipo de canal usado
    'channel_response',         // JSON con respuesta del proveedor
    'external_id',              // ID externo del proveedor
    'is_test',                  // ¿Es una notificación de prueba?
];
```

---

## 4. Servicios de Notificaciones

### 4.1 NotificationService (Notificaciones Sociales)

**Archivo**: `/app/Services/NotificationService.php`

Servicio principal para notificaciones sociales (posts, comentarios, reacciones).

#### Métodos Principales:

```php
// Notificar sobre un nuevo post
public function notifyNewPost(SocialPost $post): void

// Notificar sobre nuevo comentario
public function notifyNewComment(SocialPost $post, $comment, User $commenter): void

// Notificar sobre nueva reacción
public function notifyNewReaction(SocialPost $post, string $reactionType, User $reactor): void

// Notificar sobre nuevo seguidor
public function notifyNewFollower(Company $followedCompany, Company $followerCompany, User $follower): void

// Obtener notificaciones de un usuario
public function getUserNotifications(User $user, int $limit = 10): Collection

// Contar notificaciones no leídas
public function getUnreadCount(User $user): int

// Marcar como leídas
public function markAsRead(User $user, array $notificationIds = []): bool
```

#### Características:

1. **Scope Multi-Tenant**: Todas las queries están scopeadas por `company_id`
2. **Prevención de Spam**: Evita notificaciones duplicadas en corto tiempo
3. **Broadcast**: Soporta actualización en tiempo real
4. **Relaciones**: Carga eficiente de relaciones (sender, etc.)

---

### 4.2 StockNotificationService (Alertas de Stock)

**Archivo**: `/app/Services/StockNotificationService.php`

Servicio completo para notificaciones de alertas de inventario.

#### Métodos Principales:

```php
// Enviar notificación para alerta específica
public function notifyAlert(StockAlert $alert): void

// Enviar batch de notificaciones
public function notifyBatchAlerts(Collection $alerts): void

// Enviar resumen diario
public function sendDailySummary(?int $companyId = null): array

// Enviar alertas críticas inmediatas
public function sendCriticalAlerts(?int $companyId = null): array

// Obtener usuarios a notificar según severidad
protected function getUsersToNotify(int $companyId, string $severity): Collection

// Enviar notificación de predicción de agotamiento
public function notifyDepletionPrediction(int $companyId, array $predictions): void

// Obtener estadísticas de notificaciones
public function getNotificationStats(?int $companyId = null, int $days = 7): array

// Limpiar notificaciones antiguas
public function cleanupOldNotifications(int $daysToKeep = 30): int

// Actualizar preferencias del usuario
public function updateUserNotificationPreferences(User $user, array $preferences): void
```

#### Características:

1. **Filtrado por Rol**:
   - Críticas: Admins y Managers
   - Normales: Solo Admins
   
2. **Múltiples Canales**: Email y Database

3. **Resumen Diario**: Agrupación de alertas

4. **Queue Prioritarios**: Alertas críticas sin delay

---

## 5. Estructuras de Tablas

### 5.1 Tabla: social_notifications

```sql
CREATE TABLE social_notifications (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    company_id BIGINT NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    sender_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    type ENUM('new_post', 'post_comment', 'post_reaction', 'post_mention', 'new_follower') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    data JSON NULL,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    -- Índices
    INDEX idx_company_user_read (company_id, user_id, read_at),
    INDEX idx_company_type_created (company_id, type, created_at)
);
```

### 5.2 Tabla: stock_movements

```sql
CREATE TABLE stock_movements (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    company_id BIGINT NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    stockable_type VARCHAR(255) NOT NULL,
    stockable_id BIGINT NOT NULL,
    type ENUM('in', 'out', 'adjustment') NOT NULL,
    reason ENUM('initial_stock', 'purchase', 'sale', 'return', 'damage', 'adjustment', 'production', 'transfer') NOT NULL,
    quantity INT NOT NULL,
    previous_stock INT NOT NULL,
    new_stock INT NOT NULL,
    unit_cost DECIMAL(12, 4) NULL,
    total_cost DECIMAL(12, 2) NULL,
    reference_type VARCHAR(255) NULL,
    reference_id BIGINT NULL,
    batch_number VARCHAR(255) NULL,
    notes TEXT NULL,
    metadata JSON NULL,
    user_id BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Índices
    INDEX idx_company_stockable (company_id, stockable_type, stockable_id),
    INDEX idx_company_type_created (company_id, type, created_at),
    INDEX idx_company_reason_created (company_id, reason, created_at),
    INDEX idx_reference (reference_type, reference_id)
);
```

### 5.3 Tabla: stock_alerts

```sql
CREATE TABLE stock_alerts (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    company_id BIGINT NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    stockable_type VARCHAR(255) NOT NULL,
    stockable_id BIGINT NOT NULL,
    type ENUM('low_stock', 'out_of_stock', 'critical_low', 'reorder_point', 'excess_stock', 'movement_anomaly') NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    status ENUM('active', 'acknowledged', 'resolved', 'dismissed') DEFAULT 'active',
    current_stock INT NOT NULL,
    min_stock INT NULL,
    threshold_value INT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    metadata JSON NULL,
    triggered_at TIMESTAMP NOT NULL,
    acknowledged_at TIMESTAMP NULL,
    resolved_at TIMESTAMP NULL,
    acknowledged_by BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
    resolved_by BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
    auto_resolvable BOOLEAN DEFAULT TRUE,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Índices
    INDEX idx_company_status_severity (company_id, status, severity),
    INDEX idx_company_stockable (company_id, stockable_type, stockable_id),
    INDEX idx_triggered_status (triggered_at, status),
    INDEX idx_type_status (type, status)
);
```

### 5.4 Tabla: notification_channels

```sql
CREATE TABLE notification_channels (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    type ENUM('email', 'slack', 'teams', 'discord', 'webhook', 'sms', 'push', 'database') DEFAULT 'email',
    status ENUM('active', 'inactive', 'testing') DEFAULT 'active',
    config JSON NOT NULL,
    rate_limits JSON NULL,
    retry_settings JSON NULL,
    default_template VARCHAR(255) NULL,
    format_settings JSON NULL,
    filters JSON NULL,
    business_hours JSON NULL,
    allowed_event_types JSON NULL,
    priority INT DEFAULT 1,
    supports_realtime BOOLEAN DEFAULT FALSE,
    supports_bulk BOOLEAN DEFAULT FALSE,
    total_sent INT DEFAULT 0,
    total_delivered INT DEFAULT 0,
    total_failed INT DEFAULT 0,
    last_used_at TIMESTAMP NULL,
    last_error TEXT NULL,
    created_by BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Índices
    INDEX idx_type_status (type, status),
    INDEX idx_status_priority (status, priority),
    INDEX idx_created_by_type (created_by, type)
);
```

### 5.5 Tabla: notification_rules

```sql
CREATE TABLE notification_rules (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    status ENUM('active', 'inactive', 'draft') DEFAULT 'draft',
    event_type VARCHAR(255) NOT NULL,
    conditions JSON NOT NULL,
    filters JSON NULL,
    recipients JSON NOT NULL,
    recipient_type ENUM('static', 'dynamic', 'role_based') DEFAULT 'static',
    recipient_rules JSON NULL,
    channels JSON NOT NULL,
    channel_priorities JSON NULL,
    require_all_channels BOOLEAN DEFAULT FALSE,
    delivery_timing ENUM('immediate', 'delayed', 'scheduled', 'business_hours') DEFAULT 'immediate',
    delay_minutes INT NULL,
    schedule_config JSON NULL,
    business_hours_config JSON NULL,
    rate_limit_enabled BOOLEAN DEFAULT FALSE,
    max_per_hour INT NULL,
    max_per_day INT NULL,
    deduplicate BOOLEAN DEFAULT TRUE,
    dedupe_window_minutes INT DEFAULT 60,
    template VARCHAR(255) NULL,
    template_variables JSON NULL,
    custom_message TEXT NULL,
    attachments JSON NULL,
    escalation_enabled BOOLEAN DEFAULT FALSE,
    escalation_rules JSON NULL,
    escalation_delay_minutes INT DEFAULT 30,
    priority ENUM('low', 'normal', 'high', 'critical') DEFAULT 'normal',
    bypass_quiet_hours BOOLEAN DEFAULT FALSE,
    require_acknowledgment BOOLEAN DEFAULT FALSE,
    total_triggered INT DEFAULT 0,
    total_sent INT DEFAULT 0,
    total_delivered INT DEFAULT 0,
    total_failed INT DEFAULT 0,
    last_triggered_at TIMESTAMP NULL,
    success_rate DECIMAL(5, 2) NULL,
    created_by BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    notes TEXT NULL,
    tags JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Índices
    INDEX idx_event_type_status (event_type, status),
    INDEX idx_status_priority (status, priority),
    INDEX idx_created_by_status (created_by, status),
    INDEX idx_delivery_timing_status (delivery_timing, status)
);
```

### 5.6 Tabla: notification_logs

```sql
CREATE TABLE notification_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    notification_rule_id BIGINT NULL REFERENCES notification_rules(id) ON DELETE SET NULL,
    notification_channel_id BIGINT NULL REFERENCES notification_channels(id) ON DELETE SET NULL,
    event_type VARCHAR(255) NOT NULL,
    event_id VARCHAR(255) NULL,
    event_data JSON NULL,
    recipient_type VARCHAR(255) NOT NULL,
    recipient_identifier VARCHAR(255) NOT NULL,
    recipient_name VARCHAR(255) NULL,
    subject VARCHAR(255) NULL,
    message TEXT NOT NULL,
    attachments JSON NULL,
    template_used VARCHAR(255) NULL,
    status ENUM('pending', 'sent', 'delivered', 'failed', 'bounced', 'opened', 'clicked') DEFAULT 'pending',
    sent_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    opened_at TIMESTAMP NULL,
    clicked_at TIMESTAMP NULL,
    error_message TEXT NULL,
    error_details JSON NULL,
    retry_count INT DEFAULT 0,
    next_retry_at TIMESTAMP NULL,
    channel_type VARCHAR(255) NOT NULL,
    channel_response JSON NULL,
    external_id VARCHAR(255) NULL,
    processing_time_ms INT NULL,
    delivery_time_ms INT NULL,
    priority ENUM('low', 'normal', 'high', 'critical') DEFAULT 'normal',
    is_test BOOLEAN DEFAULT FALSE,
    is_bulk BOOLEAN DEFAULT FALSE,
    metadata JSON NULL,
    batch_id VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Índices
    INDEX idx_rule_status (notification_rule_id, status),
    INDEX idx_event_type_created (event_type, created_at),
    INDEX idx_status_sent (status, sent_at),
    INDEX idx_recipient_event (recipient_identifier, event_type),
    INDEX idx_batch_status (batch_id, status),
    INDEX idx_channel_type_status (channel_type, status, created_at)
);
```

---

## 6. Ejemplos de Código

### 6.1 Enviar Notificación de Nuevo Post

**Ubicación**: `app/Filament/Widgets/CreatePostWidget.php`

```php
// Crear post
$post = SocialPost::create([
    'company_id' => auth()->user()->company_id,
    'user_id' => auth()->id(),
    'content' => $data['content'],
    'is_public' => $data['is_public'] ?? true,
]);

// Notificar a otros usuarios
$notificationService = app(NotificationService::class);
$notificationService->notifyNewPost($post);

// Toast de confirmación
\Filament\Notifications\Notification::make()
    ->success()
    ->title('Post creado exitosamente')
    ->send();
```

### 6.2 Registrar Movimiento de Stock y Disparar Alertas

```php
use App\Models\StockMovement;
use App\Models\StockAlert;

// 1. Registrar movimiento
$movement = StockMovement::create([
    'company_id' => $product->company_id,
    'stockable_type' => get_class($product),
    'stockable_id' => $product->id,
    'type' => 'out',  // Salida
    'reason' => 'sale',
    'quantity' => 100,
    'previous_stock' => $product->stock,
    'new_stock' => $product->stock - 100,
    'user_id' => auth()->id(),
]);

// 2. Actualizar stock
$product->update(['stock' => $movement->new_stock]);

// 3. Disparar evento
StockUpdated::dispatch($movement, $movement->previous_stock, $movement->new_stock);

// 4. Verificar si se deben crear alertas
if ($product->stock < $product->min_stock) {
    StockAlert::create([
        'company_id' => $product->company_id,
        'stockable_type' => get_class($product),
        'stockable_id' => $product->id,
        'type' => $product->stock === 0 ? 'out_of_stock' : 'low_stock',
        'severity' => $product->stock === 0 ? 'critical' : 'high',
        'status' => 'active',
        'current_stock' => $product->stock,
        'min_stock' => $product->min_stock,
        'title' => "Stock bajo: {$product->name}",
        'message' => "El stock de {$product->name} es {$product->stock} unidades",
        'triggered_at' => now(),
        'auto_resolvable' => true,
    ]);
    
    // Notificar admins
    $stockNotificationService = app(StockNotificationService::class);
    $alert = StockAlert::latest()->first();
    $stockNotificationService->notifyAlert($alert);
}
```

### 6.3 Enviar Notificación de Nueva Orden de Compra

**Ubicación**: `app/Notifications/PurchaseOrderCreated.php`

```php
namespace App\Notifications;

use App\Models\PurchaseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PurchaseOrderCreated extends Notification
{
    use Queueable;

    public function __construct(public int $purchaseOrderId) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];  // Email y Dashboard
    }

    public function toMail(object $notifiable): MailMessage
    {
        $purchaseOrder = PurchaseOrder::findOrFail($this->purchaseOrderId);

        return (new MailMessage)
            ->subject("Nueva Orden de Pedido #{$purchaseOrder->order_number}")
            ->view('emails.purchase-order.created', [
                'purchaseOrder' => $purchaseOrder,
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        $purchaseOrder = PurchaseOrder::findOrFail($this->purchaseOrderId);

        return [
            'purchase_order_id' => $purchaseOrder->id,
            'order_number' => $purchaseOrder->order_number,
            'supplier_company' => $purchaseOrder->supplierCompany->name,
            'total_amount' => $purchaseOrder->total_amount,
            'message' => "Nueva orden de pedido enviada",
        ];
    }
}

// Uso
$user->notify(new PurchaseOrderCreated($purchaseOrder->id));
```

### 6.4 Obtener Notificaciones No Leídas de un Usuario

```php
use App\Services\NotificationService;

$notificationService = app(NotificationService::class);

// Obtener últimas 10 notificaciones
$notifications = $notificationService->getUserNotifications(auth()->user(), 10);

// Contar no leídas
$unreadCount = $notificationService->getUnreadCount(auth()->user());

// Marcar todas como leídas
$notificationService->markAsRead(auth()->user());

// Marcar específicas como leídas
$notificationService->markAsRead(auth()->user(), [1, 2, 3]);
```

### 6.5 Configurar Alerta de Alertas Críticas de Stock

```php
use App\Services\StockNotificationService;

$stockService = app(StockNotificationService::class);

// Enviar alertas críticas cada hora
$results = $stockService->sendCriticalAlerts();

echo "Alertas enviadas: {$results['sent']} de {$results['alerts']}";

// Obtener estadísticas de los últimos 7 días
$stats = $stockService->getNotificationStats(companyId: null, days: 7);

// Enviar resumen diario
$dailyResults = $stockService->sendDailySummary();

// Limpiar notificaciones antiguas (más de 30 días)
$deleted = $stockService->cleanupOldNotifications(30);
```

---

## 7. Flujos de Envío/Recepción

### 7.1 Flujo de Notificación de Post Social

```
1. Usuario crea post
    ↓
2. Event: DocumentCreated o CreatePostWidget
    ↓
3. NotificationService::notifyNewPost() es invocada
    ↓
4. Consulta usuarios de la empresa (excepto autor)
    ↓
5. Crea SocialNotification para cada usuario
    ↓
6. Marca como no leída (read_at = NULL)
    ↓
7. Dashboard/Widget muestra notificaciones
    ↓
8. Usuario hace clic en notificación
    ↓
9. markAsRead() actualiza read_at
```

### 7.2 Flujo de Alerta de Stock

```
1. Stock actualizado (vía StockMovement)
    ↓
2. Event: StockUpdated
    ↓
3. Listener verifica condiciones de alerta
    ↓
4. Crear StockAlert (si es necesario)
    ↓
5. StockNotificationService::notifyAlert() invocada
    ↓
6. Determinar usuarios a notificar (por rol)
    ↓
7. Usar StockAlertNotification (Mail + Database)
    ↓
8. Email enviado a través de queue (MAIL_MAILER=log)
    ↓
9. Entrada en tabla notifications
    ↓
10. Usuario ve en dashboard
    ↓
11. Usuario puede acknowledge, resolve, o dismiss
```

### 7.3 Flujo de Notificación de Orden de Compra

```
1. PurchaseOrder creada/actualizada
    ↓
2. Event: PurchaseOrderStatusChanged
    ↓
3. Listener: NotifyPurchaseOrderStatusChange
    ↓
4. PurchaseOrderCreated o PurchaseOrderStatusChanged Notification
    ↓
5. Canales: mail, database
    ↓
6. Mail enviado con PDF adjunto
    ↓
7. Entrada en tabla notifications
    ↓
8. Usuario recibe email (o log en desarrollo)
    ↓
9. Usuario ve en dashboard de notificaciones
```

---

## 8. Configuración Multi-Tenant

### 8.1 Scopes Automáticos

**Trait**: `app/Models/Concerns/BelongsToTenant.php`

```php
trait BelongsToTenant
{
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(function ($query) {
            if ($companyId = TenantContext::id()) {
                return $query->where('company_id', $companyId);
            }
        });
    }

    public function scopeForTenant($query, $companyId = null)
    {
        return $query->where('company_id', $companyId ?? TenantContext::id());
    }
}
```

### 8.2 Modelos con Scope Tenant

| Modelo | Scope | Nota |
|--------|-------|------|
| `SocialNotification` | ✓ `forTenant()` | Aislada por empresa |
| `StockAlert` | ✓ `forTenant()` | Aislada por empresa |
| `StockMovement` | ✓ `forTenant()` | Aislada por empresa |
| `NotificationChannel` | ✗ Global | Solo Super Admin |
| `NotificationRule` | ✗ Global | Solo Super Admin |
| `NotificationLog` | ✗ Global | Log global (con filtro empresa) |

### 8.3 Protección en Servicios

```php
// NotificationService
public function getUserNotifications(User $user, int $limit = 10): Collection
{
    return SocialNotification::forTenant($user->company_id)  // ← Scope obligatorio
        ->where('user_id', $user->id)
        ->with(['sender'])
        ->recent()
        ->limit($limit)
        ->get();
}

// StockNotificationService
public function sendDailySummary(?int $companyId = null): array
{
    $companyId = $companyId ?? TenantContext::id();  // ← Default al tenant actual
    
    $todayAlerts = StockAlert::forTenant($companyId)
        ->active()
        ->where('triggered_at', '>=', now()->startOfDay())
        ->with(['stockable'])
        ->get();
    
    // ...
}
```

---

## 9. Casos de Uso Implementados

### 9.1 Notificaciones Sociales

```
Tipo: new_post
Descripción: Se notifica a todos los usuarios de una empresa cuando se crea un post público
Disparador: CreatePostWidget, SocialPostWidget
Destinatarios: Usuarios de la misma empresa (excepto autor)

Tipo: post_comment
Descripción: Se notifica al autor del post cuando alguien comenta
Disparador: SocialPostWidget
Destinatarios: Autor del post

Tipo: post_reaction
Descripción: Se notifica al autor cuando reaccionan a su post (con deduplicación)
Disparador: SocialPostWidget
Destinatarios: Autor del post

Tipo: new_follower
Descripción: Se notifica a admins/managers cuando una empresa nueva sigue
Disparador: CompanyFollowController
Destinatarios: Admin/Managers de la empresa seguida
```

### 9.2 Alertas de Stock

```
Tipo: low_stock
Severidad: high
Descripción: Stock por debajo del mínimo
Destinatarios: Admins

Tipo: out_of_stock
Severidad: critical
Descripción: Stock en cero
Destinatarios: Admins, Managers
Canales: Email inmediato, Dashboard

Tipo: critical_low
Severidad: critical
Descripción: Stock crítico (< 20% del mínimo)
Destinatarios: Admins, Managers
Canales: Email inmediato, SMS, Dashboard

Tipo: reorder_point
Severidad: high
Descripción: Stock llegó al punto de reorden
Destinatarios: Admins

Tipo: excess_stock
Severidad: low
Descripción: Stock excesivo
Destinatarios: Admins

Tipo: movement_anomaly
Severidad: medium
Descripción: Movimiento anómalo detectado
Destinatarios: Admins, Managers
```

### 9.3 Notificaciones de Órdenes de Compra

```
Evento: PurchaseOrderCreated
Disparador: Creación de PurchaseOrder
Canales: Email + Database
Contenido: Resumen y PDF de la orden

Evento: PurchaseOrderStatusChanged
Disparador: Cambio de estado (sent → confirmed, etc.)
Canales: Email + Database
Contenido: Nuevo estado y detalles

Widget: PurchaseOrderNotificationsWidget
Descripción: Muestra órdenes que requieren atención
Condiciones:
  - Estado "sent" hace más de 3 días
  - Entrega esperada en los próximos 2 días
```

---

## 10. Configuración y Setup

### 10.1 Archivo .env

```env
# Queue (para procesamiento asíncrono)
QUEUE_CONNECTION=database

# Mail (para envío de notificaciones)
MAIL_MAILER=log                          # En desarrollo: log
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="GrafiRed"
```

### 10.2 Configuración en AppServiceProvider

```php
namespace App\Providers;

use App\Services\StockNotificationService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Registrar singleton
        $this->app->singleton(StockNotificationService::class, function ($app) {
            return new StockNotificationService();
        });
    }

    public function boot(): void
    {
        // ...
    }
}
```

### 10.3 Cola de Trabajos

```bash
# Procesar queue
php artisan queue:work database

# Escuchar cambios y procesar
php artisan queue:listen database

# Procesar un número específico de jobs
php artisan queue:work --max-tries=3
```

---

## 11. Integraciones Futuras

El sistema está diseñado para soportar fácilmente nuevas integraciones:

### Canales Planeados:
1. **Slack**: Webhooks a canales de Slack
2. **Microsoft Teams**: Integración con Teams
3. **Discord**: Notificaciones en servidores Discord
4. **SMS**: Integraciones con Twilio
5. **Push Notifications**: FCM para notificaciones mobile
6. **Webhook Custom**: Para sistemas externos

### Implementación:
```php
// Agregar canal personalizado
$channel = NotificationChannel::create([
    'name' => 'Mi Slack Bot',
    'type' => 'slack',
    'status' => 'active',
    'config' => [
        'webhook_url' => 'https://hooks.slack.com/...',
        'channel' => '#notificaciones',
        'username' => 'GrafiRed Bot',
    ],
    'rate_limits' => [
        'per_minute' => 30,
        'per_hour' => 300,
    ],
    'created_by' => auth()->id(),
]);

// Crear regla para usar el canal
$rule = NotificationRule::create([
    'name' => 'Alertas Críticas a Slack',
    'event_type' => 'stock_alert_critical',
    'status' => 'active',
    'conditions' => ['severity' => 'critical'],
    'recipients' => ['admins'], // roles dinámicos
    'recipient_type' => 'role_based',
    'channels' => [$channel->id],
    'delivery_timing' => 'immediate',
    'priority' => 'critical',
    'created_by' => auth()->id(),
]);
```

---

## 12. Buenas Prácticas

### 12.1 Crear Nueva Notificación

```php
// 1. Crear evento (si no existe)
namespace App\Events;
class MiEvento {
    use Dispatchable, SerializesModels;
    public function __construct(public Model $model) {}
}

// 2. Crear clase Notification
namespace App\Notifications;
class MiNotificacion extends Notification {
    public function via($notifiable): array {
        return ['database', 'mail'];  // ← Canales
    }
    
    public function toDatabase($notifiable): array {
        return ['message' => '...'];
    }
}

// 3. Usar en controlador/evento
$user->notify(new MiNotificacion());
```

### 12.2 Prevenir Spam

```php
// Verificar si ya existe notificación reciente
$recent = SocialNotification::where('user_id', $recipient->id)
    ->where('sender_id', $sender->id)
    ->where('type', 'post_reaction')
    ->whereJsonContains('data->post_id', $post->id)
    ->where('created_at', '>', now()->subMinutes(5))
    ->first();

if ($recent) {
    return;  // No enviar notificación duplicada
}

// Crear nueva
SocialNotification::create([...]);
```

### 12.3 Manejar Errores

```php
try {
    $user->notify(new CriticalAlert($alert));
} catch (\Exception $e) {
    // Log del error
    \Log::error('Notification failed', [
        'user_id' => $user->id,
        'exception' => $e->getMessage(),
    ]);
    
    // Fallback a método alternativo
    $this->fallbackNotify($user, $alert);
}
```

---

## Conclusión

El sistema de notificaciones de GrafiRed 3.0 es un sistema robusto y extensible que:

✓ Soporta múltiples tipos de notificaciones (social, stock, sistema)
✓ Está completamente aislado por empresa (multi-tenant)
✓ Permite configuración avanzada (canales, reglas, logs)
✓ Integra con Laravel Queue para procesamiento asíncrono
✓ Está diseñado para futuras expansiones (Slack, Teams, SMS, etc.)
✓ Proporciona auditoría completa (logs detallados)
✓ Previene duplicados y spam
✓ Soporta roles y permisos granulares

