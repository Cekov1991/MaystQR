<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * An operational alert for whoever runs the application — never for a customer.
 *
 * Deliberately not queued: most of these fire because something in the billing
 * pipeline is already broken, and an alert that needs a healthy worker to reach
 * anyone is no alert at all.
 */
class BillingAlert extends Notification
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        private readonly string $summary,
        private readonly array $context = [],
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
        $message = (new MailMessage)
            ->error()
            ->subject('['.config('app.name').'] Billing needs attention')
            ->line($this->summary);

        foreach ($this->context as $key => $value) {
            $message->line(sprintf('**%s:** %s', $key, $this->readable($value)));
        }

        return $message->line('The application log holds the full context.');
    }

    private function readable(mixed $value): string
    {
        return match (true) {
            $value === null => 'null',
            is_bool($value) => $value ? 'true' : 'false',
            is_scalar($value) => (string) $value,
            default => (string) json_encode($value),
        };
    }
}
