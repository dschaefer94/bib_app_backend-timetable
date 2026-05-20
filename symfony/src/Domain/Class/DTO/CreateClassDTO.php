<?php

namespace App\Domain\Class\DTO;

final class CreateClassDTO
{
    public function __construct(
        public readonly string $klassenname,
        public readonly ?string $icalLink = null
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            klassenname: $data['klassenname'] ?? '',
            icalLink: $data['ical_link'] ?? null
        );
    }
}

