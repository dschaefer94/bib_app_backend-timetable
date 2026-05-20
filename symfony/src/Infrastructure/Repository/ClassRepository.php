<?php

namespace App\Infrastructure\Repository;

use App\Domain\Class\Entity\ClassEntity;
use App\Domain\Class\Repository\ClassRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

class ClassRepository implements ClassRepositoryInterface
{
    public function __construct(private EntityManagerInterface $entityManager) {}

    public function findById(int $klassenId): ?ClassEntity
    {
        return $this->entityManager->find(ClassEntity::class, $klassenId);
    }

    public function findByName(string $klassenname): ?ClassEntity
    {
        return $this->entityManager->getRepository(ClassEntity::class)
            ->findOneBy(['klassenname' => $klassenname]);
    }

    public function findAll(): array
    {
        return $this->entityManager->getRepository(ClassEntity::class)
            ->findBy([], ['klassenname' => 'ASC']);
    }

    public function save(ClassEntity $class): void
    {
        $this->entityManager->persist($class);
        $this->entityManager->flush();
    }

    public function update(ClassEntity $class): void
    {
        $this->entityManager->persist($class);
        $this->entityManager->flush();
    }

    public function delete(ClassEntity $class): void
    {
        $this->entityManager->remove($class);
        $this->entityManager->flush();
    }
}

