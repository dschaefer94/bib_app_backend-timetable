<?php

namespace App\Domain\Calendar\DTO;

final class CalendarEventDTO
{
    public function __construct(
        public readonly string $terminId,
        public readonly string $summary,
        public readonly string $start,
        public readonly string $end,
        public readonly string $location
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            terminId: $data['termin_id'] ?? '',
            summary: $data['summary'] ?? '',
            start: $data['start'] ?? '',
            end: $data['end'] ?? '',
            location: $data['location'] ?? ''
        );
    }

    public function toArray(): array
    {
        return [
            'termin_id' => $this->terminId,
            'summary' => $this->summary,
            'start' => $this->start,
            'end' => $this->end,
            'location' => $this->location,
        ];
    }
}

