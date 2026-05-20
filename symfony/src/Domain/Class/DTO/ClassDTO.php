<?php

namespace App\Domain\Class\DTO;

final class ClassDTO
{
    public function __construct(
        public readonly int $klassenId,
        public readonly string $klassenname
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            klassenId: $data['klassen_id'] ?? 0,
            klassenname: $data['klassenname'] ?? ''
        );
    }

    public function toArray(): array
    {
        return [
            'klassen_id' => $this->klassenId,
            'klassenname' => $this->klassenname,
        ];
    }
}

