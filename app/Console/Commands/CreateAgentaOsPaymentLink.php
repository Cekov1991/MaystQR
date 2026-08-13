<?php

namespace App\Console\Commands;

use App\Services\AgentaOS\AgentaOsClient;
use App\Services\AgentaOS\AgentaOsException;
use Illuminate\Console\Command;

/**
 * Creates the single product-wide subscription payment link.
 *
 * Run once per environment. A command rather than dashboard clicks so that
 * test mode and live mode are created identically and reproducibly.
 */
class CreateAgentaOsPaymentLink extends Command
{
    protected $signature = 'agentaos:create-payment-link
                            {--name= : Product name shown at checkout}
                            {--description= : Longer description shown at checkout}';

    protected $description = 'Create the yearly subscription payment link at AgentaOS';

    public function handle(AgentaOsClient $agentaOs): int
    {
        if (blank(config('services.agentaos.key'))) {
            $this->components->error('AGENTAOS_API_KEY is not configured.');

            return self::FAILURE;
        }

        $name = $this->option('name') ?: config('app.name').' Yearly';
        $description = $this->option('description')
            ?: sprintf(
                'Keeps your dynamic QR codes online. Up to %d dynamic and %d static QR codes.',
                config('subscription.quotas.dynamic'),
                config('subscription.quotas.static'),
            );

        $this->components->info(sprintf(
            'Creating a %s %s subscription link…',
            number_format((float) config('subscription.price'), 2),
            config('subscription.currency'),
        ));

        try {
            $link = $agentaOs->createSubscriptionPaymentLink($name, $description);
        } catch (AgentaOsException $exception) {
            $this->components->error($exception->getMessage());

            if ($exception->requestId !== null) {
                $this->line("  Request id: {$exception->requestId}");
            }

            return self::FAILURE;
        }

        $this->newLine();
        $this->components->info('Payment link created.');
        $this->components->twoColumnDetail('Environment', $link['environment'] ?? 'unknown');
        $this->components->twoColumnDetail('Checkout URL', $link['checkoutUrl'] ?? 'none');
        $this->newLine();
        $this->line('Add this to your .env:');
        $this->line("  AGENTAOS_PAYMENT_LINK_ID={$link['id']}");
        $this->newLine();

        return self::SUCCESS;
    }
}
