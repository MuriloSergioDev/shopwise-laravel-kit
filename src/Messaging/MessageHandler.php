<?php

declare(strict_types=1);

namespace Shopwise\Kit\Messaging;

/**
 * Handles one event type. Runs inside a DB transaction that also records the message id,
 * so it must only write to the service's own database (plus Outbox::record for follow-ups).
 */
interface MessageHandler
{
    public function handle(Envelope $envelope): void;
}
