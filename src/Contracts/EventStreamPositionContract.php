<?php

declare(strict_types=1);

namespace Sifrious\EventContract\Contracts;

use JsonSerializable;

interface EventStreamPositionContract extends JsonSerializable
{
    /** @return array{stream: array<string, mixed>, sequence: int} */
    public function toArray(): array;
}
