<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class TaskBadgeWebPushNotification extends Notification
{
    public function __construct(
        private readonly string $title,
        private readonly string $body,
        private readonly string $url,
        private readonly string $tag,
        private readonly int $badgeCount,
    ) {}

    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title($this->title)
            ->body($this->body)
            ->icon('/images/icons/pkg-pwa-2026-192.png')
            ->badge('/images/icons/pkg-pwa-2026-192.png')
            ->tag($this->tag)
            ->renotify()
            ->vibrate([180, 80, 180])
            ->data([
                'url' => $this->url,
                'badge_count' => max(0, $this->badgeCount),
            ])
            ->options([
                'TTL' => 3600,
                'urgency' => 'normal',
                'topic' => substr(hash('sha256', $this->tag), 0, 32),
            ]);
    }
}
