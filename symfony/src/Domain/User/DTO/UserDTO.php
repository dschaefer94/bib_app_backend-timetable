<?php

namespace App\Domain\User\DTO;

final class UserDTO
{
    public function __construct(
        public readonly string $benutzerId,
        public readonly bool $istAdmin,
        public readonly string $name,
        public readonly string $vorname,
        public readonly string $email,
        public readonly string $klassenname
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            benutzerId: $data['benutzer_id'] ?? '',
            istAdmin: $data['ist_admin'] ?? false,
            name: $data['name'] ?? '',
            vorname: $data['vorname'] ?? '',
            email: $data['email'] ?? '',
            klassenname: $data['klassenname'] ?? ''
        );
    }

    public function toArray(): array
    {
        return [
            'benutzer_id' => $this->benutzerId,
            'ist_admin' => $this->istAdmin,
            'name' => $this->name,
            'vorname' => $this->vorname,
            'email' => $this->email,
            'klassenname' => $this->klassenname,
        ];
    }
}

