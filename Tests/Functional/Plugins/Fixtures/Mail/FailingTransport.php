<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Plugins\Fixtures\Mail;

use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

/**
 * A mail transport that delivers nothing, selected as `MAIL.transport`. Core passes the
 * mail settings to the constructor, and a class without one ignores them.
 *
 * It fails the way a real transport fails, with a `TransportException`. A mail to
 * `unexpected@example.com` fails with a `RuntimeException` instead, an error outside the
 * contract of a transport.
 */
final class FailingTransport implements TransportInterface
{
    public const UNEXPECTED_RECIPIENT = 'unexpected@example.com';
    public const UNEXPECTED_ERROR_CODE = 1790674697;

    public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
    {
        $recipients = $message instanceof Email ? $message->getTo() : [];
        if (($recipients[0] ?? null)?->getAddress() === self::UNEXPECTED_RECIPIENT) {
            throw new \RuntimeException('The test transport failed unexpectedly.', self::UNEXPECTED_ERROR_CODE);
        }

        throw new TransportException('The test transport refuses every mail.');
    }

    public function __toString(): string
    {
        return 'failing://';
    }
}
