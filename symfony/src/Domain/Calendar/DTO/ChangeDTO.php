<?php

namespace App\Domain\Calendar\DTO;

final class ChangeDTO
{
    public function __construct(
        public readonly string $terminId,
        public readonly string $label,
        public readonly ?string $summaryAlt = null,
        public readonly ?string $startAlt = null,
        public readonly ?string $endAlt = null,
        public readonly ?string $locationAlt = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            terminId: $data['termin_id'] ?? '',
            label: $data['label'] ?? '',
            summaryAlt: $data['summary_alt'] ?? null,
            startAlt: $data['start_alt'] ?? null,
            endAlt: $data['end_alt'] ?? null,
            locationAlt: $data['location_alt'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'termin_id' => $this->terminId,
            'label' => $this->label,
            'summary_alt' => $this->summaryAlt,
            'start_alt' => $this->startAlt,
            'end_alt' => $this->endAlt,
            'location_alt' => $this->locationAlt,
        ];
    }
}

