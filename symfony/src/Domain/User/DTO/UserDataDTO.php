<?php

namespace App\Domain\User\DTO;

final class UserDataDTO
{
    public function __construct(
        public readonly string $benutzerId,
        public readonly string $name,
        public readonly string $vorname,
        public readonly string $email,
        public readonly ?int $klassenId,
        public readonly ?string $klassenname
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            benutzerId: $data['benutzer_id'] ?? '',
            name: $data['name'] ?? '',
            vorname: $data['vorname'] ?? '',
            email: $data['email'] ?? '',
            klassenId: $data['klassen_id'] ?? null,
            klassenname: $data['klassenname'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'benutzer_id' => $this->benutzerId,
            'name' => $this->name,
            'vorname' => $this->vorname,
            'email' => $this->email,
            'klassen_id' => $this->klassenId,
            'klassenname' => $this->klassenname,
        ];
    }
}

