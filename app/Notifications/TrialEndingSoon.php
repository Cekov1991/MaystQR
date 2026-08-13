<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrialEndingSoon extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $days = $notifiable->trialDaysRemaining();
        $dynamicCount = $notifiable->qrCodes()->where('type', 'dynamic')->count();

        $message = (new MailMessage)
            ->subject(sprintf(
                'Your %s trial ends in %d %s',
                config('app.name'),
                $days,
                str('day')->plural($days),
            ))
            ->greeting("Hi {$notifiable->name},")
            ->line(sprintf(
                'Your free trial ends on %s.',
                $notifiable->trial_ends_at->format('j F Y'),
            ));

        $message->line($dynamicCount > 0
            ? sprintf(
                'After that, your %d dynamic QR %s will stop working when scanned until you subscribe.',
                $dynamicCount,
                str('code')->plural($dynamicCount),
            )
            : 'After that, you will need a subscription to create dynamic QR codes.');

        return $message
            ->action('Subscribe — $'.$this->price().'/year', route('filament.admin.pages.billing'))
            ->line('Your static QR codes are free forever and are not affected.');
    }

    private function price(): string
    {
        return rtrim(rtrim(number_format((float) config('subscription.price'), 2), '0'), '.');
    }
}
