<?php

namespace App\Filament\Widgets;

use App\Models\SocialNotification;
use App\Services\NotificationService;
use Filament\Widgets\Widget;

class NotificationDropdownWidget extends Widget
{
    protected string $view = 'filament.widgets.notification-dropdown-widget';

    protected int | string | array $columnSpan = 'full';

    protected $listeners = ['notifications-updated' => 'refreshNotifications'];

    public function getNotifications()
    {
        $notificationService = app(NotificationService::class);
        return $notificationService->getUserNotifications(auth()->user(), 10);
    }

    public function getUnreadCount()
    {
        $notificationService = app(NotificationService::class);
        return $notificationService->getUnreadCount(auth()->user());
    }

    public function markAsRead($notificationId = null)
    {
        $notificationService = app(NotificationService::class);

        if ($notificationId) {
            $notificationService->markAsRead(auth()->user(), [$notificationId]);
        } else {
            $notificationService->markAsRead(auth()->user());
        }

        $this->dispatch('$refresh');
    }

    public function refreshNotifications()
    {
        $this->dispatch('$refresh');
    }

    public function markAllAsRead()
    {
        $notificationService = app(NotificationService::class);
        $notificationService->markAsRead(auth()->user());
        $this->dispatch('$refresh');
    }
}