<?php

namespace App\Services;

use App\Models\SocialNotification;
use App\Models\SocialPost;
use App\Models\User;
use App\Models\Company;
use Illuminate\Support\Collection;
use App\Services\TenantContext;

class NotificationService
{
    /**
     * Notificar sobre un nuevo post a todos los usuarios de la empresa
     */
    public function notifyNewPost(SocialPost $post): void
    {
        // Solo notificar si es público
        if (!$post->is_public) {
            return;
        }

        // Obtener todos los usuarios de la empresa excepto el autor
        $users = User::forTenant($post->company_id)
            ->where('id', '!=', $post->user_id)
            ->get();

        foreach ($users as $user) {
            SocialNotification::create([
                'company_id' => $post->company_id,
                'user_id' => $user->id,
                'sender_id' => $post->user_id,
                'type' => SocialNotification::TYPE_NEW_POST,
                'title' => 'Nuevo post de ' . $post->author->name,
                'message' => $this->truncateMessage($post->content, 100),
                'data' => [
                    'post_id' => $post->id,
                    'post_type' => $post->post_type,
                    'post_url' => '/admin/home#post-' . $post->id,
                ],
            ]);
        }

        // Emitir evento para actualización en tiempo real
        $this->broadcastNotification($post->company_id, 'new-post-notification');
    }

    /**
     * Notificar sobre un nuevo comentario al autor del post
     */
    public function notifyNewComment(SocialPost $post, $comment, User $commenter): void
    {
        // No notificar si es el mismo usuario
        if ($post->user_id === $commenter->id) {
            return;
        }

        SocialNotification::create([
            'company_id' => $post->company_id,
            'user_id' => $post->user_id, // Al autor del post
            'sender_id' => $commenter->id,
            'type' => SocialNotification::TYPE_POST_COMMENT,
            'title' => $commenter->name . ' comentó tu post',
            'message' => $this->truncateMessage($comment['content'] ?? '', 100),
            'data' => [
                'post_id' => $post->id,
                'comment_id' => $comment['id'] ?? null,
                'post_url' => '/admin/home#post-' . $post->id,
            ],
        ]);

        // Emitir evento para actualización en tiempo real
        $this->broadcastNotification($post->company_id, 'new-comment-notification', $post->user_id);
    }

    /**
     * Notificar sobre una nueva reacción al autor del post
     */
    public function notifyNewReaction(SocialPost $post, string $reactionType, User $reactor): void
    {
        // No notificar si es el mismo usuario
        if ($post->user_id === $reactor->id) {
            return;
        }

        // Verificar si ya existe una notificación reciente del mismo tipo
        $existingNotification = SocialNotification::where('user_id', $post->user_id)
            ->where('sender_id', $reactor->id)
            ->where('type', SocialNotification::TYPE_POST_REACTION)
            ->whereJsonContains('data->post_id', $post->id)
            ->where('created_at', '>', now()->subMinutes(5))
            ->first();

        if ($existingNotification) {
            return; // Evitar spam de notificaciones
        }

        $reactionEmoji = match($reactionType) {
            'like' => '👍',
            'love' => '❤️',
            'laugh' => '😂',
            'wow' => '😮',
            'sad' => '😢',
            'angry' => '😠',
            default => '👍',
        };

        SocialNotification::create([
            'company_id' => $post->company_id,
            'user_id' => $post->user_id,
            'sender_id' => $reactor->id,
            'type' => SocialNotification::TYPE_POST_REACTION,
            'title' => $reactor->name . ' reaccionó a tu post',
            'message' => 'Le dio ' . $reactionEmoji . ' a tu publicación',
            'data' => [
                'post_id' => $post->id,
                'reaction_type' => $reactionType,
                'post_url' => '/admin/home#post-' . $post->id,
            ],
        ]);

        // Emitir evento para actualización en tiempo real
        $this->broadcastNotification($post->company_id, 'new-reaction-notification', $post->user_id);
    }

    /**
     * Notificar sobre un nuevo seguidor
     */
    public function notifyNewFollower(Company $followedCompany, Company $followerCompany, User $follower): void
    {
        // Obtener usuarios de la empresa seguida (admins, managers)
        $users = User::forTenant($followedCompany->id)
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['Super Admin', 'Company Admin', 'Manager']);
            })
            ->get();

        foreach ($users as $user) {
            SocialNotification::create([
                'company_id' => $followedCompany->id,
                'user_id' => $user->id,
                'sender_id' => $follower->id,
                'type' => SocialNotification::TYPE_NEW_FOLLOWER,
                'title' => 'Nueva empresa siguiendo',
                'message' => $followerCompany->name . ' ahora sigue a tu empresa',
                'data' => [
                    'follower_company_id' => $followerCompany->id,
                    'follower_company_name' => $followerCompany->name,
                    'follower_company_url' => $followerCompany->getProfileUrl(),
                    'follower_user_id' => $follower->id,
                    'follower_user_name' => $follower->name,
                ],
            ]);
        }

        // Emitir evento para actualización en tiempo real
        $this->broadcastNotification($followedCompany->id, 'new-follower-notification');
    }

    /**
     * Obtener notificaciones de un usuario
     */
    public function getUserNotifications(User $user, int $limit = 10): Collection
    {
        return SocialNotification::forTenant($user->company_id)
            ->where('user_id', $user->id)
            ->with(['sender'])
            ->recent()
            ->limit($limit)
            ->get();
    }

    /**
     * Obtener contador de notificaciones no leídas
     */
    public function getUnreadCount(User $user): int
    {
        return SocialNotification::forTenant($user->company_id)
            ->where('user_id', $user->id)
            ->unread()
            ->count();
    }

    /**
     * Marcar notificaciones como leídas
     */
    public function markAsRead(User $user, array $notificationIds = []): bool
    {
        $query = SocialNotification::forTenant($user->company_id)
            ->where('user_id', $user->id);

        if (!empty($notificationIds)) {
            $query->whereIn('id', $notificationIds);
        } else {
            $query->unread();
        }

        return $query->update(['read_at' => now()]) > 0;
    }

    /**
     * Truncar mensaje para notificación
     */
    private function truncateMessage(string $message, int $length = 100): string
    {
        return strlen($message) > $length ? substr($message, 0, $length) . '...' : $message;
    }

    /**
     * Emitir evento para notificación en tiempo real
     */
    private function broadcastNotification(int $companyId, string $event, ?int $specificUserId = null): void
    {
        // Por ahora usaremos Livewire events, pero se puede integrar con WebSockets más adelante
        // Este método se puede extender para usar Laravel Broadcasting con Pusher/Redis
    }
}
