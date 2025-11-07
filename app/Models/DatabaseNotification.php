<?php

namespace App\Models;

use Illuminate\Notifications\DatabaseNotification as BaseDatabaseNotification;

class DatabaseNotification extends BaseDatabaseNotification
{
    /**
     * Override para asegurar que las notificaciones no sean filtradas por tenant scope
     * ya que las notificaciones pertenecen a usuarios, no directamente a tenants.
     */
    protected static function booted()
    {
        parent::booted();

        // No aplicar ningún scope global adicional
        // Las notificaciones ya están filtradas por notifiable_id (user_id)
    }
}
