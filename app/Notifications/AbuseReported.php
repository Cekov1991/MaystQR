<?php

namespace App\Notifications;

use App\Models\QrCode;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A report that a QR code on our domain leads somewhere harmful.
 *
 * Sent on demand to the support address. It resolves the reported code to its
 * owner and current destination at send time, because a report we have to look up
 * by hand is a report that waits — and a dynamic code's destination can change
 * between the report being filed and anyone reading it.
 */
class AbuseReported extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array{code_url: string, reason: string, details: string, reporter_email?: ?string}  $report
     */
    public function __construct(private array $report) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $qrCode = $this->reportedCode();

        $message = (new MailMessage)
            ->subject(sprintf(
                '[Abuse report] %s — %s',
                config('app.name'),
                $this->reasonLabel(),
            ))
            ->line('Someone reported a QR code through the public report form.')
            ->line('**Reported:** '.$this->report['code_url'])
            ->line('**Reason:** '.$this->reasonLabel())
            ->line('**What they said:** '.$this->report['details']);

        if ($qrCode === null) {
            $message->line(
                '**We could not match this to a code in the database.** It may be a '
                .'mistyped reference, a static code that never touched our servers, '
                .'or a code that has since been deleted.'
            );
        } else {
            $message
                ->line('**Code:** '.$qrCode->name.' (`'.$qrCode->short_url.'`, '.$qrCode->type.')')
                ->line('**Currently points at:** '.($qrCode->destination_url ?? 'no destination set'))
                ->line('**Owner:** '.($qrCode->user?->email ?? 'no owner record'))
                ->action('Open the code in the panel', route(
                    'filament.admin.resources.qr-codes.edit',
                    ['record' => $qrCode->getKey()],
                ));
        }

        // Coalesced rather than compared directly: this runs on the queue, so an
        // absent key would fail the job and lose the report behind a retry log
        // instead of surfacing as a request error anyone would notice.
        $reporterEmail = $this->report['reporter_email'] ?? null;

        $message->line($reporterEmail !== null
            ? 'Reporter left an address for a reply: '.$reporterEmail
            : 'The reporter did not leave an address, so there is nobody to reply to.');

        return $message;
    }

    /**
     * Matches the reported reference against a short URL. Reporters paste whole
     * links, type fragments off a poster, or copy the code out of a scanner app,
     * so the last path-looking segment is the best candidate.
     */
    private function reportedCode(): ?QrCode
    {
        $candidate = trim($this->report['code_url']);
        $candidate = rtrim(parse_url($candidate, PHP_URL_PATH) ?: $candidate, '/');
        $shortUrl = basename($candidate);

        if ($shortUrl === '') {
            return null;
        }

        return QrCode::with('user')->where('short_url', $shortUrl)->first();
    }

    private function reasonLabel(): string
    {
        return str($this->report['reason'])->replace('_', ' ')->title()->value();
    }
}
