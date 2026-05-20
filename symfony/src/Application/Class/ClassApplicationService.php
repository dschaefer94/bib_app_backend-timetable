<?php

namespace App\Application\Class;

use App\Domain\Class\Service\ClassService;
use App\Domain\Class\DTO\ClassDTO;
use App\Domain\Class\DTO\CreateClassDTO;
use App\Application\Shared\UserContext;
use App\Shared\Exception\UnauthorizedException;

/**
 * Application Service für Class Use Cases
 */
class ClassApplicationService
{
    public function __construct(
        private ClassService $classService,
        private UserContext $userContext
    ) {}

    /**
     * Use Case: Alle Klassen abrufen
     */
    public function getAllClasses(): array
    {
        return $this->classService->getAllClasses();
    }

    /**
     * Use Case: Klasse des Benutzers abrufen
     */
    public function getUserClass(): array
    {
        if (!$this->userContext->isAuthenticated()) {
            throw UnauthorizedException::notLoggedIn();
        }

        $klassenname = $this->userContext->getKlassenname();
        if (!$klassenname) {
            throw UnauthorizedException::notLoggedIn();
        }

        $classDTO = $this->classService->getClassByName($klassenname);
        return [$classDTO->toArray()];
    }

    /**
     * Use Case: Neue Klasse erstellen
     */
    public function createClass(CreateClassDTO $dto): array
    {
        $classDTO = $this->classService->createClass($dto);
        return [
            'erfolg' => true,
            'klassenname' => $classDTO->klassenname
        ];
    }

    /**
     * Use Case: Klasse aktualisieren
     */
    public function updateClass(int $klassenId, CreateClassDTO $dto): array
    {
        $classDTO = $this->classService->updateClass($klassenId, $dto, $this->userContext->getUserId());
        return ['erfolg' => true];
    }

    /**
     * Use Case: Klasse löschen (nur Admin)
     */
    public function deleteClass(int $klassenId): array
    {
        $this->classService->deleteClass($klassenId, $this->userContext->isAdmin());
        return ['erfolg' => true];
    }
}

