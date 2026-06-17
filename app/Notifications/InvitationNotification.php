<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Queued invitation email sent to a prospective tenant member (Improvement 4.2).
 *
 * Delivered as an on-demand notification because the invitee is not a User yet.
 */
class InvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $tenantName,
        public string $token,
        public ?string $invitedBy = null,
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
        $url = route('invitation.show', ['token' => $this->token]);

        $message = (new MailMessage)
            ->subject("You've been invited to {$this->tenantName}")
            ->line(($this->invitedBy ? "{$this->invitedBy} invited you" : 'You have been invited')." to join {$this->tenantName}.")
            ->action('Accept invitation', $url)
            ->line('If you did not expect this invitation, you can ignore this email.');

        return $message;
    }
}
