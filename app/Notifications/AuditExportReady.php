<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

/**
 * Notifies a user that their audit-log export is ready, with a short-lived
 * signed download link (Improvement 4.2).
 */
class AuditExportReady extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Tenant $tenant,
        public string $filename,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = URL::temporarySignedRoute(
            'admin.audit.export.download',
            now()->addDays(3),
            ['tenant' => $this->tenant->slug, 'filename' => $this->filename],
        );

        return (new MailMessage)
            ->subject("Audit export ready — {$this->tenant->name}")
            ->line('Your audit-log export has finished generating.')
            ->action('Download CSV', $url)
            ->line('This link expires in 3 days and requires you to be signed in.');
    }
}
