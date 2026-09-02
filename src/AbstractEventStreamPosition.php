<?php

declare(strict_types=1);

namespace Sifrious\EventContract;

use InvalidArgumentException;
use Sifrious\EventContract\Contracts\EventStreamPositionContract;
use Sifrious\ReferenceContract\CrossPackageReference;

abstract readonly class AbstractEventStreamPosition implements EventStreamPositionContract
{
    public function __construct(
        public CrossPackageReference $stream,
        public int $sequence,
    ) {
        if ($sequence < 1) {
            throw new InvalidArgumentException('Event stream sequences must be positive integers.');
        }
    }

    public function toArray(): array
    {
        return [
            'stream' => $this->stream->toArray(),
            'sequence' => $this->sequence,
        ];
    }

    /** @param array<string, mixed> $serialized */
    public static function fromArray(array $serialized): static
    {
        $stream = $serialized['stream'] ?? null;
        $sequence = $serialized['sequence'] ?? null;

        if (! is_array($stream) || ! is_int($sequence)) {
            throw new InvalidArgumentException('Serialized stream positions require a stream reference and integer sequence.');
        }

        return new static(CrossPackageReference::fromArray($stream), $sequence);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
