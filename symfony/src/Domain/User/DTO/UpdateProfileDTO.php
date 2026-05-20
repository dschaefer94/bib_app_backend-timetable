<?php

namespace App\Domain\User\DTO;

final class UpdateProfileDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $vorname,
        public readonly string $email,
        public readonly ?string $passwort,
        public readonly ?string $passwortConfirm,
        public readonly ?int $klassenId
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            name: $data['name'] ?? '',
            vorname: $data['vorname'] ?? '',
            email: $data['email'] ?? '',
            passwort: $data['passwort'] ?? null,
            passwortConfirm: $data['passwort_confirm'] ?? null,
            klassenId: isset($data['klassen_id']) ? (int)$data['klassen_id'] : null
        );
    }
}

