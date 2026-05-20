<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class ReportGeneratedNotification extends Notification
{
    use Queueable;

    public $downloadUrl;

    public function __construct($downloadUrl)
    {
        $this->downloadUrl = $downloadUrl;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'Rapport Prêt',
            'message' => 'Votre rapport mensuel est prêt à être téléchargé.',
            'type' => 'document',
            'action_url' => $this->downloadUrl
        ];
    }
}
