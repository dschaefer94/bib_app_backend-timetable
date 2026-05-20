<?php

namespace App\Domain\Class\Repository;

use App\Domain\Class\Entity\ClassEntity;

interface ClassRepositoryInterface
{
    public function findById(int $klassenId): ?ClassEntity;

    public function findByName(string $klassenname): ?ClassEntity;

    public function findAll(): array;

    public function save(ClassEntity $class): void;

    public function update(ClassEntity $class): void;

    public function delete(ClassEntity $class): void;
}

