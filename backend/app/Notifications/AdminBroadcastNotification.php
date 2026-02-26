<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AdminBroadcastNotification extends Notification
{
    use Queueable;

    public function __construct(
        private string $title,
        private string $message,
        private ?string $imageUrl = null,
        private ?string $actionUrl = null,
        private string $audienceType = 'all_user',
        private array $meta = []
    ) {}

    /**
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'type' => 'admin_broadcast',
            'image_url' => $this->imageUrl,
            'action_url' => $this->actionUrl,
            'link' => $this->actionUrl,
            'audience_type' => $this->audienceType,
            'meta' => $this->meta,
            'created_at' => now()->toIso8601String(),
        ];
    }
}
