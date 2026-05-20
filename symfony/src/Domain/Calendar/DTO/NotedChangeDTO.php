<?php

namespace App\Domain\Calendar\DTO;

final class NotedChangeDTO
{
    public function __construct(
        public readonly string $terminId
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            terminId: $data['termin_id'] ?? ''
        );
    }

    public function toArray(): array
    {
        return [
            'termin_id' => $this->terminId,
        ];
    }
}

