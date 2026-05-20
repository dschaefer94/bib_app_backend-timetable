<?php

namespace App\Domain\Password\DTO;

final class ResetPasswordDTO
{
    public function __construct(
        public readonly string $token,
        public readonly string $password
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            token: $data['token'] ?? '',
            password: $data['password'] ?? ''
        );
    }
}

