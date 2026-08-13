<?php

namespace Tests\Feature;

use Tests\TestCase;

class MailConfigurationTest extends TestCase
{
    /**
     * The default mailer was once derived from APP_ENV, which made it
     * impossible to override: phpunit.xml asked for `array` and got a real SMTP
     * transport instead, so every test that sent mail opened a live connection
     * and registration returned a 500.
     */
    public function test_the_default_mailer_is_the_one_the_environment_asked_for(): void
    {
        $this->assertSame('array', config('mail.default'));
    }

    /**
     * The named mailers each read their own prefix, so the standard MAIL_*
     * variables only reach the generic `smtp` entry. It has to exist for those
     * variables to be usable at all — and `failover` references it by name.
     */
    public function test_a_generic_smtp_mailer_exists_and_reads_the_standard_variables(): void
    {
        $this->assertSame('smtp', config('mail.mailers.smtp.transport'));

        foreach (config('mail.mailers.failover.mailers') as $mailer) {
            $this->assertIsArray(
                config("mail.mailers.{$mailer}"),
                "The failover mailer references [{$mailer}], which is not configured.",
            );
        }
    }
}
