<?php

declare(strict_types=1);

use Sifrious\EventContract\AbstractEventEnvelope;
use Sifrious\EventContract\Contracts\EventEnvelopeContract;
use Sifrious\EventContract\EventEnvelope;

require dirname(__DIR__).'/vendor/autoload.php';

$assertions = 0;

$assert = static function (bool $condition, string $message) use (&$assertions): void {
    $assertions++;
    if (! $condition) {
        throw new RuntimeException($message);
    }
};

$fixture = static function (string $name): array {
    $value = json_decode(
        file_get_contents(__DIR__."/Fixtures/{$name}.json"),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    if (! is_array($value)) {
        throw new RuntimeException("Fixture {$name} did not decode to an object.");
    }

    return $value;
};

foreach (['aleph-observation-ingested', 'titan-work-started', 'logres-execution-completed'] as $name) {
    $serialized = $fixture($name);
    $event = EventEnvelope::fromArray($serialized);
    $queued = json_decode(json_encode($event, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);

    $assert($event instanceof EventEnvelopeContract, "{$name} did not implement the envelope contract.");
    $assert($event instanceof AbstractEventEnvelope, "{$name} did not inherit from the provider-neutral ABC.");
    $assert($event->toArray() === $serialized, "{$name} changed the Funes v1 array representation.");
    $assert($queued === $serialized, "{$name} changed the Funes v1 JSON representation.");
    $assert(EventEnvelope::fromArray($queued)->fingerprint() === $event->fingerprint(), "{$name} changed its fingerprint after a round trip.");
    $assert($event->idempotencyKey() === $serialized['id'], "{$name} did not use the event id as its idempotency key.");
}

$accepted = [];
$effects = 0;
$accept = static function (EventEnvelope $event) use (&$accepted, &$effects): string {
    $known = $accepted[$event->idempotencyKey()] ?? null;
    if ($known === $event->fingerprint()) {
        return 'replayed';
    }
    if ($known !== null) {
        throw new RuntimeException('event-id-conflict');
    }

    $accepted[$event->idempotencyKey()] = $event->fingerprint();
    $effects++;

    return 'accepted';
};

$event = EventEnvelope::fromArray($fixture('aleph-observation-ingested'));
$assert($accept($event) === 'accepted', 'The first delivery was not accepted.');
$assert($accept(EventEnvelope::fromArray($event->toArray())) === 'replayed', 'An identical replay was not idempotent.');
$assert($effects === 1, 'An identical replay repeated the consumer effect.');

try {
    $accept(EventEnvelope::fromArray([
        ...$event->toArray(),
        'payload' => ['resource' => 'github:issue_43', 'content_hash' => 'sha256:changed'],
    ]));
    throw new RuntimeException('Conflicting event-id reuse was accepted.');
} catch (RuntimeException $exception) {
    $assert($exception->getMessage() === 'event-id-conflict', 'Conflicting event-id reuse produced the wrong failure.');
}

foreach ([
    'empty subjects' => static fn (): EventEnvelope => new EventEnvelope(
        'evt_invalid', 'invalid.test', 'sifrious/test', '1', new DateTimeImmutable('2026-01-01T00:00:00Z'), null,
        new DateTimeImmutable('2026-01-01T00:00:00Z'), [], null, null, [], null, [],
    ),
    'invalid chronology' => static fn (): EventEnvelope => EventEnvelope::fromArray([
        ...$event->toArray(),
        'occurred_at' => '2026-08-29T10:00:00.000000+00:00',
    ]),
    'list payload' => static fn (): EventEnvelope => EventEnvelope::fromArray([
        ...$event->toArray(),
        'payload' => ['not', 'an', 'object'],
    ]),
] as $name => $operation) {
    try {
        $operation();
        throw new RuntimeException("Invalid {$name} was accepted.");
    } catch (InvalidArgumentException) {
        $assert(true, "Invalid {$name} was rejected.");
    }
}

fwrite(STDOUT, "event-contract: {$assertions} assertions passed\n");
