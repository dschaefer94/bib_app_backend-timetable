<?php

namespace App\Application\User;

use App\Domain\Password\Service\PasswordResetService;
use App\Domain\Password\DTO\RequestPasswordResetDTO;
use App\Domain\Password\DTO\ResetPasswordDTO;

/**
 * Application Service für Password Reset Use Cases
 */
class PasswordApplicationService
{
    public function __construct(
        private PasswordResetService $passwordResetService
    ) {}

    /**
     * Use Case: Passwort-Reset anfordern
     */
    public function requestPasswordReset(RequestPasswordResetDTO $dto): array
    {
        return $this->passwordResetService->requestPasswordReset($dto);
    }

    /**
     * Use Case: Passwort zurücksetzen
     */
    public function resetPassword(ResetPasswordDTO $dto): array
    {
        return $this->passwordResetService->resetPassword($dto);
    }
}

