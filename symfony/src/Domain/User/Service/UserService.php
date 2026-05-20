<?php

namespace App\Domain\User\Service;

use App\Domain\User\Entity\User;
use App\Domain\User\Entity\PersonalData;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\Repository\PersonalDataRepositoryInterface;
use App\Domain\User\DTO\UserDTO;
use App\Domain\User\DTO\UserDataDTO;
use App\Domain\User\DTO\RegisterUserDTO;
use App\Domain\User\DTO\UpdateProfileDTO;
use App\Domain\Class\Repository\ClassRepositoryInterface;
use App\Shared\Exception\EntityNotFoundException;
use App\Shared\Exception\ValidationException;
use App\Shared\Exception\ConflictException;
use App\Shared\ValueObject\Email;
use Ramsey\Uuid\Uuid;

class UserService
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PersonalDataRepositoryInterface $personalDataRepository,
        private ClassRepositoryInterface $classRepository
    ) {}

    /**
     * Registriert einen neuen Benutzer
     */
    public function registerUser(RegisterUserDTO $dto): bool
    {
        // Validierung
        $this->validateRegisterData($dto);

        // Prüfe ob Email bereits existiert
        if ($this->userRepository->findByEmail($dto->email)) {
            throw ConflictException::userAlreadyExists();
        }

        // Erstelle neue Benutzer-ID
        $benutzerId = Uuid::uuid4()->toString();

        // Erstelle User
        $email = new Email($dto->email);
        $hashedPassword = password_hash($dto->passwort, PASSWORD_BCRYPT);
        $user = new User($benutzerId, $email, $hashedPassword);

        // Finde Klasse
        $klasse = $this->classRepository->findByName($dto->klassenname);
        if (!$klasse) {
            throw EntityNotFoundException::classNotFound();
        }

        // Erstelle PersonalData
        $personalData = new PersonalData($benutzerId, $dto->name, $dto->vorname);
        $personalData->setKlasse($klasse);
        $user->setPersonalData($personalData);

        // Speichere
        $this->userRepository->save($user);
        $this->personalDataRepository->save($personalData);

        return true;
    }

    /**
     * Findet einen Benutzer nach ID
     */
    public function getUserById(string $benutzerId): UserDTO
    {
        $user = $this->userRepository->findById($benutzerId);
        if (!$user) {
            throw EntityNotFoundException::userNotFound();
        }

        $personalData = $user->getPersonalData();
        $klassenname = $personalData?->getKlasse()?->getKlassenname() ?? '';

        return new UserDTO(
            benutzerId: $user->getBenutzerId(),
            istAdmin: $user->isAdmin(),
            name: $personalData?->getName() ?? '',
            vorname: $personalData?->getVorname() ?? '',
            email: $user->getEmail(),
            klassenname: $klassenname
        );
    }

    /**
     * Findet einen Benutzer nach Email
     */
    public function getUserByEmail(string $email): ?UserDTO
    {
        $user = $this->userRepository->findByEmail($email);
        if (!$user) {
            return null;
        }

        $personalData = $user->getPersonalData();
        $klassenname = $personalData?->getKlasse()?->getKlassenname() ?? '';

        return new UserDTO(
            benutzerId: $user->getBenutzerId(),
            istAdmin: $user->isAdmin(),
            name: $personalData?->getName() ?? '',
            vorname: $personalData?->getVorname() ?? '',
            email: $user->getEmail(),
            klassenname: $klassenname
        );
    }

    /**
     * Holt die Profildaten eines Benutzers
     */
    public function getUserData(string $benutzerId): UserDataDTO
    {
        $personalData = $this->personalDataRepository->findByBenutzerId($benutzerId);
        if (!$personalData) {
            throw EntityNotFoundException::userNotFound();
        }

        return new UserDataDTO(
            benutzerId: $benutzerId,
            name: $personalData->getName(),
            vorname: $personalData->getVorname(),
            email: $personalData->getBenutzer()->getEmail(),
            klassenId: $personalData->getKlasse()?->getKlassenId(),
            klassenname: $personalData->getKlasse()?->getKlassenname()
        );
    }

    /**
     * Aktualisiert das Profil eines Benutzers
     */
    public function updateProfile(string $benutzerId, UpdateProfileDTO $dto): UserDataDTO
    {
        // Validierung
        $this->validateUpdateProfileData($dto);

        $user = $this->userRepository->findById($benutzerId);
        if (!$user) {
            throw EntityNotFoundException::userNotFound();
        }

        $personalData = $user->getPersonalData();
        if (!$personalData) {
            throw EntityNotFoundException::userNotFound();
        }

        // Aktualisiere Personaldaten
        $personalData->setName($dto->name);
        $personalData->setVorname($dto->vorname);

        if ($dto->klassenId !== null) {
            $klasse = $this->classRepository->findById($dto->klassenId);
            if ($klasse) {
                $personalData->setKlasse($klasse);
            }
        }

        // Aktualisiere Email
        $user->setEmail(trim($dto->email));

        // Aktualisiere Passwort wenn vorhanden
        if ($dto->passwort) {
            $hashedPassword = password_hash($dto->passwort, PASSWORD_DEFAULT);
            $user->setPasswort($hashedPassword);
        }

        // Speichere Änderungen
        $this->userRepository->update($user);
        $this->personalDataRepository->update($personalData);

        return new UserDataDTO(
            benutzerId: $user->getBenutzerId(),
            name: $personalData->getName(),
            vorname: $personalData->getVorname(),
            email: $user->getEmail(),
            klassenId: $personalData->getKlasse()?->getKlassenId(),
            klassenname: $personalData->getKlasse()?->getKlassenname()
        );
    }

    /**
     * Löscht einen Benutzer
     */
    public function deleteUser(string $benutzerId): void
    {
        $user = $this->userRepository->findById($benutzerId);
        if (!$user) {
            throw EntityNotFoundException::userNotFound();
        }

        $personalData = $user->getPersonalData();
        if ($personalData) {
            $this->personalDataRepository->delete($personalData);
        }

        $this->userRepository->delete($user);
    }

    /**
     * Validiert Registrierungsdaten
     */
    private function validateRegisterData(RegisterUserDTO $dto): void
    {
        $errors = [];

        if (empty($dto->email)) {
            $errors[] = 'Email ist erforderlich';
        } elseif (!filter_var($dto->email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Ungültige Email-Adresse';
        }

        if (empty($dto->passwort)) {
            $errors[] = 'Passwort ist erforderlich';
        }

        if (empty($dto->name)) {
            $errors[] = 'Name ist erforderlich';
        }

        if (empty($dto->vorname)) {
            $errors[] = 'Vorname ist erforderlich';
        }

        if (empty($dto->klassenname)) {
            $errors[] = 'Klassenname ist erforderlich';
        }

        if (!empty($errors)) {
            throw ValidationException::withErrors($errors);
        }
    }

    /**
     * Validiert Update-Profildaten
     */
    private function validateUpdateProfileData(UpdateProfileDTO $dto): void
    {
        $errors = [];

        if (empty($dto->name) || empty($dto->vorname)) {
            $errors[] = 'Name und Vorname sind erforderlich';
        }

        if (!empty($dto->passwort) && $dto->passwort !== $dto->passwortConfirm) {
            $errors[] = 'Passwörter stimmen nicht überein';
        }

        if (!empty($errors)) {
            throw ValidationException::withErrors($errors);
        }
    }
}

