@component('mail::message')
# Resumen Diario de Órdenes de Pedido

Buenos días {{ $user->name }},

Este es el resumen de actividad de órdenes de pedido para **{{ $company->name }}** del día {{ $today }}.

## 📈 Resumen Ejecutivo

@component('mail::panel')
**Órdenes Creadas Hoy:** {{ $recentOrders->count() }}
**Órdenes Pendientes:** {{ $pendingOrders->count() }}
**Valor Total Pendiente:** ${{ number_format($pendingOrders->sum('total_amount'), 2) }} COP
@endcomponent

@if($recentOrders->count() > 0)
## 🆕 Órdenes Creadas Hoy

@component('mail::table')
| Orden | Proveedor | Tipo | Total |
|:------|:----------|:-----|------:|
@foreach($recentOrders as $order)
| #{{ $order->order_number }} | {{ $order->supplierCompany->name }} | {{ $order->order_type === 'papel' ? 'Papel' : 'Producto' }} | ${{ number_format($order->total_amount, 2) }} |
@endforeach
@endcomponent
@else
No se crearon órdenes nuevas en el día de hoy.
@endif

@if($pendingOrders->count() > 0)
## ⏳ Órdenes Pendientes de Seguimiento

Las siguientes órdenes requieren atención:

@foreach($pendingOrders->take(10) as $order)
@component('mail::promotion')
**#{{ $order->order_number }}** - {{ $order->supplierCompany->name }}
Estado: {{ $order->status_label }} | Total: ${{ number_format($order->total_amount, 2) }}
Creada: {{ $order->created_at->format('d/m/Y') }}
@if($order->expected_delivery_date)
Entrega esperada: {{ $order->expected_delivery_date->format('d/m/Y') }}
@endif
@endcomponent
@endforeach

@if($pendingOrders->count() > 10)
*... y {{ $pendingOrders->count() - 10 }} órdenes más pendientes.*
@endif
@endif

## 📊 Estadísticas Rápidas

- **Total órdenes este mes:** {{ \App\Models\PurchaseOrder::where('company_id', $company->id)->whereMonth('created_at', now()->month)->count() }}
- **Valor promedio por orden:** ${{ number_format(\App\Models\PurchaseOrder::where('company_id', $company->id)->avg('total_amount') ?? 0, 2) }}
- **Proveedores activos:** {{ \App\Models\PurchaseOrder::where('company_id', $company->id)->distinct('supplier_company_id')->count() }}

@component('mail::button', ['url' => route('filament.admin.resources.purchase-orders.index')])
Ver Todas las Órdenes
@endcomponent

Manténganse al tanto del estado de sus órdenes para optimizar los procesos de aprovisionamiento.

Saludos,
**Sistema LitoPro**

---
*Este resumen se envía automáticamente cada día. Para cancelar estas notificaciones, contacten al administrador del sistema.*
@endcomponent