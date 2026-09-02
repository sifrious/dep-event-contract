<?php

declare(strict_types=1);

use Sifrious\EventContract\EventEnvelope;
use Sifrious\EventContract\EventStreamPosition;
use Sifrious\ReferenceContract\CrossPackageReference;

require dirname(__DIR__).'/vendor/autoload.php';

$work = new CrossPackageReference('sifrious/titan', 'work-item', 'work_01', 'revision:7');
$event = new EventEnvelope(
    'evt_titan_02',
    'work.started',
    'sifrious/titan',
    '2',
    new DateTimeImmutable('2026-08-28T10:01:00.000000+00:00'),
    null,
    new DateTimeImmutable('2026-08-28T10:01:00.100000+00:00'),
    [$work],
    'evt_aleph_01',
    'sync_01',
    [],
    null,
    ['transition' => 'queued-to-running', 'actor' => 'agent_01'],
    new EventStreamPosition(
        new CrossPackageReference('sifrious/titan', 'work-item', 'work_01'),
        7,
    ),
);

$expected = json_decode(
    file_get_contents(__DIR__.'/Fixtures/titan-work-started.json'),
    true,
    flags: JSON_THROW_ON_ERROR,
);

if ($event->toArray() !== $expected) {
    throw new RuntimeException('The standalone Titan producer changed the Funes v1 event representation.');
}

fwrite(STDOUT, "producer fixture: Titan event matches Funes v1 without Funes or Laravel\n");
