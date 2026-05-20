<?php

namespace App\Application\User;

use App\Domain\User\Service\UserService;
use App\Domain\User\DTO\RegisterUserDTO;
use App\Domain\User\DTO\UpdateProfileDTO;
use App\Domain\Class\Service\ClassService;
use App\Application\Shared\UserContext;
use App\Shared\Exception\UnauthorizedException;

/**
 * Application Service für User Use Cases
 */
class UserApplicationService
{
    public function __construct(
        private UserService $userService,
        private ClassService $classService,
        private UserContext $userContext
    ) {}

    /**
     * Use Case: Benutzer-Daten abrufen
     */
    public function getUser(): array
    {
        if (!$this->userContext->isAuthenticated()) {
            throw UnauthorizedException::notLoggedIn();
        }

        $userDTO = $this->userService->getUserById($this->userContext->getUserId());
        return $userDTO->toArray();
    }

    /**
     * Use Case: Neuen Benutzer registrieren
     */
    public function registerUser(RegisterUserDTO $dto): array
    {
        $this->userService->registerUser($dto);
        return ['benutzerAngelegt' => true];
    }

    /**
     * Use Case: Benutzerprofil abrufen
     */
    public function getProfile(): array
    {
        if (!$this->userContext->isAuthenticated()) {
            throw UnauthorizedException::notAuthenticated();
        }

        $userData = $this->userService->getUserData($this->userContext->getUserId());
        $classes = $this->classService->getAllClasses();

        return [
            'success' => true,
            'userData' => $userData->toArray(),
            'klassen' => $classes
        ];
    }

    /**
     * Use Case: Profil aktualisieren
     */
    public function updateProfile(UpdateProfileDTO $dto): array
    {
        if (!$this->userContext->isAuthenticated()) {
            throw UnauthorizedException::notAuthenticated();
        }

        $userData = $this->userService->updateProfile($this->userContext->getUserId(), $dto);
        $classes = $this->classService->getAllClasses();

        // Aktualisiere Kontext wenn Klasse sich geändert hat
        if ($userData->klassenname) {
            $this->userContext->setKlassenname($userData->klassenname);
        }

        return [
            'success' => true,
            'message' => 'Daten erfolgreich aktualisiert!',
            'userData' => $userData->toArray(),
            'klassen' => $classes
        ];
    }
}

