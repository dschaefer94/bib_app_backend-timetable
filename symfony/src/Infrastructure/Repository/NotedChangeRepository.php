<?php

namespace App\Infrastructure\Repository;

use App\Domain\Calendar\Entity\NotedChange;
use App\Domain\Calendar\Repository\NotedChangeRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

class NotedChangeRepository implements NotedChangeRepositoryInterface
{
    public function __construct(private EntityManagerInterface $entityManager) {}

    public function findByBenutzerId(string $benutzerId): array
    {
        return $this->entityManager->getRepository(NotedChange::class)
            ->findBy(['benutzerId' => $benutzerId]);
    }

    public function findByTerminId(string $terminId): ?NotedChange
    {
        return $this->entityManager->getRepository(NotedChange::class)
            ->findOneBy(['terminId' => $terminId]);
    }

    public function findByBenutzeriDAndTerminId(string $benutzerId, string $terminId): ?NotedChange
    {
        return $this->entityManager->getRepository(NotedChange::class)
            ->findOneBy(['benutzerId' => $benutzerId, 'terminId' => $terminId]);
    }

    public function save(NotedChange $notedChange): void
    {
        $this->entityManager->persist($notedChange);
        $this->entityManager->flush();
    }

    public function delete(NotedChange $notedChange): void
    {
        $this->entityManager->remove($notedChange);
        $this->entityManager->flush();
    }
}

