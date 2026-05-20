<?php

namespace App\Infrastructure\Repository;

use App\Domain\User\Entity\PersonalData;
use App\Domain\User\Repository\PersonalDataRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

class PersonalDataRepository implements PersonalDataRepositoryInterface
{
    public function __construct(private EntityManagerInterface $entityManager) {}

    public function findByBenutzerId(string $benutzerId): ?PersonalData
    {
        return $this->entityManager->getRepository(PersonalData::class)
            ->findOneBy(['benutzerId' => $benutzerId]);
    }

    /**
     * Findet alle PersonalData für eine bestimmte Klasse
     */
    public function findByClassId(int $klassenId): array
    {
        return $this->entityManager->getRepository(PersonalData::class)
            ->findBy(['klasse' => $klassenId]);
    }

    public function save(PersonalData $personalData): void
    {
        $this->entityManager->persist($personalData);
        $this->entityManager->flush();
    }

    public function update(PersonalData $personalData): void
    {
        $this->entityManager->persist($personalData);
        $this->entityManager->flush();
    }

    public function delete(PersonalData $personalData): void
    {
        $this->entityManager->remove($personalData);
        $this->entityManager->flush();
    }
}

