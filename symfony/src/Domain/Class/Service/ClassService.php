<?php

namespace App\Domain\Class\Service;

use App\Domain\Class\Entity\ClassEntity;
use App\Domain\Class\Repository\ClassRepositoryInterface;
use App\Domain\Class\DTO\ClassDTO;
use App\Domain\Class\DTO\CreateClassDTO;
use App\Domain\User\Repository\PersonalDataRepositoryInterface;
use App\Shared\Exception\EntityNotFoundException;
use App\Shared\Exception\ValidationException;
use App\Shared\Exception\ConflictException;
use App\Shared\Exception\UnauthorizedException;

class ClassService
{
    public function __construct(
        private ClassRepositoryInterface $classRepository,
        private PersonalDataRepositoryInterface $personalDataRepository
    ) {}

    /**
     * Ruft alle Klassen ab
     */
    public function getAllClasses(): array
    {
        $classes = $this->classRepository->findAll();
        return array_map(
            fn(ClassEntity $class) => $this->mapToDTO($class),
            $classes
        );
    }

    /**
     * Findet eine Klasse nach ID
     */
    public function getClassById(int $klassenId): ClassDTO
    {
        $class = $this->classRepository->findById($klassenId);
        if (!$class) {
            throw EntityNotFoundException::classNotFound();
        }

        return $this->mapToDTO($class);
    }

    /**
     * Findet eine Klasse nach Name
     */
    public function getClassByName(string $klassenname): ClassDTO
    {
        $class = $this->classRepository->findByName($klassenname);
        if (!$class) {
            throw EntityNotFoundException::classNotFound();
        }

        return $this->mapToDTO($class);
    }

    /**
     * Erstellt eine neue Klasse
     */
    public function createClass(CreateClassDTO $dto): ClassDTO
    {
        // Validierung
        if (empty($dto->klassenname)) {
            throw ValidationException::missingField('klassenname');
        }

        // Prüfe Duplikat
        if ($this->classRepository->findByName($dto->klassenname)) {
            throw ConflictException::classAlreadyExists();
        }

        // Erstelle Klasse
        $class = new ClassEntity($dto->klassenname, $dto->icalLink);
        $this->classRepository->save($class);

        // TODO: Bei icalLink vorhanden: kalenderupdater aufrufen

        return $this->mapToDTO($class);
    }

    /**
     * Aktualisiert eine Klasse
     */
    public function updateClass(int $klassenId, CreateClassDTO $dto, ?string $currentUserId = null): ClassDTO
    {
        $class = $this->classRepository->findById($klassenId);
        if (!$class) {
            throw EntityNotFoundException::classNotFound();
        }

        // Validierung
        if (empty($dto->klassenname)) {
            throw ValidationException::missingField('klassenname');
        }

        // Wenn Klassenname geändert wurde: Prüfe Duplikat
        if ($class->getKlassenname() !== $dto->klassenname) {
            if ($this->classRepository->findByName($dto->klassenname)) {
                throw ConflictException::classAlreadyExists();
            }
        }

        // Aktualisiere
        $class->setKlassenname($dto->klassenname);
        if ($dto->icalLink !== null) {
            $class->setIcalLink($dto->icalLink);
            // TODO: kalenderupdater aufrufen
        }

        $this->classRepository->update($class);

        return $this->mapToDTO($class);
    }

    /**
     * Löscht eine Klasse (nur Admin)
     */
    public function deleteClass(int $klassenId, bool $isAdmin = false): void
    {
        if (!$isAdmin) {
            throw UnauthorizedException::adminRequired();
        }

        // DummyKlasse (ID=1) darf nicht gelöscht werden
        if ($klassenId === 1) {
            throw UnauthorizedException::adminRequired();
        }

        $class = $this->classRepository->findById($klassenId);
        if (!$class) {
            throw EntityNotFoundException::classNotFound();
        }

        // Setze alle Benutzer dieser Klasse auf DummyKlasse (ID=1)
        $personalDataList = $this->personalDataRepository->findByClassId($klassenId);
        $dummyClass = $this->classRepository->findById(1);

        foreach ($personalDataList as $personalData) {
            $personalData->setKlasse($dummyClass);
            $this->personalDataRepository->update($personalData);
        }

        // TODO: Lösche alle zugehörigen Tabellen (alter_stundenplan, neuer_stundenplan, aenderungen)

        $this->classRepository->delete($class);
    }

    /**
     * Mapped ClassEntity zu DTO
     */
    private function mapToDTO(ClassEntity $class): ClassDTO
    {
        return new ClassDTO(
            klassenId: $class->getKlassenId() ?? 0,
            klassenname: $class->getKlassenname()
        );
    }
}

