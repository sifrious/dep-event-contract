<?php

declare(strict_types=1);

namespace Sifrious\EventContract\Contracts;

use JsonSerializable;

interface EventEnvelopeContract extends JsonSerializable
{
    public function idempotencyKey(): string;

    public function fingerprint(): string;

    /** @return array<string, mixed> */
    public function toArray(): array;
}
