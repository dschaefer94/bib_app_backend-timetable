<?php

namespace App\Domain\User\DTO;

final class RegisterUserDTO
{
    public function __construct(
        public readonly string $email,
        public readonly string $passwort,
        public readonly string $name,
        public readonly string $vorname,
        public readonly string $klassenname
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            email: $data['email'] ?? '',
            passwort: $data['passwort'] ?? '',
            name: $data['name'] ?? '',
            vorname: $data['vorname'] ?? '',
            klassenname: $data['klassenname'] ?? ''
        );
    }
}

