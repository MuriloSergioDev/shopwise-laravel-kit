<?php

declare(strict_types=1);

namespace Shopwise\Kit\Messaging;

use Closure;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Trace\Propagation\TraceContextPropagator;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use Throwable;

/**
 * W3C trace context over Kafka headers, so one checkout is one trace across services.
 */
final class Tracing
{
    /**
     * @return array<string, string>
     */
    public static function currentHeaders(): array
    {
        /** @var array<string, string> $carrier */
        $carrier = [];
        TraceContextPropagator::getInstance()->inject($carrier);

        /** @var array<string, string> $carrier */
        return $carrier;
    }

    /**
     * Runs $work inside a CONSUMER span whose parent is the producer's trace context.
     *
     * @template T
     *
     * @param  array<string, string>  $headers
     * @param  Closure(): T  $work
     * @return T
     */
    public static function consume(Envelope $envelope, string $topic, array $headers, Closure $work): mixed
    {
        $parent = TraceContextPropagator::getInstance()->extract($headers);
        $provider = Globals::tracerProvider();
        $span = $provider->getTracer('shopwise-kit')
            ->spanBuilder("{$topic} process")
            ->setParent($parent)
            ->setSpanKind(SpanKind::KIND_CONSUMER)
            ->setAttribute('messaging.system', 'kafka')
            ->setAttribute('messaging.destination.name', $topic)
            ->setAttribute('messaging.message.id', $envelope->id)
            ->setAttribute('shopwise.event.type', $envelope->type)
            ->startSpan();
        $scope = $span->activate();

        try {
            return $work();
        } catch (Throwable $e) {
            $span->recordException($e);
            $span->setStatus(StatusCode::STATUS_ERROR, $e->getMessage());

            throw $e;
        } finally {
            $scope->detach();
            $span->end();
            // Consumers are long-running and often idle; the batch processor only exports when another
            // span ends, so flush per message or the trace stays incomplete until the next one.
            if (method_exists($provider, 'forceFlush')) {
                $provider->forceFlush();
            }
        }
    }
}
