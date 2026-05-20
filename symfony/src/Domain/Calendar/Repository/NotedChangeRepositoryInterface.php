<?php

namespace App\Domain\Calendar\Repository;

use App\Domain\Calendar\Entity\NotedChange;

interface NotedChangeRepositoryInterface
{
    public function findByBenutzerId(string $benutzerId): array;

    public function findByTerminId(string $terminId): ?NotedChange;

    public function findByBenutzeriDAndTerminId(string $benutzerId, string $terminId): ?NotedChange;

    public function save(NotedChange $notedChange): void;

    public function delete(NotedChange $notedChange): void;
}

