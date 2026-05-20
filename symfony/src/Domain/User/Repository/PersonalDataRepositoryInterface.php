<?php

namespace App\Domain\User\Repository;

use App\Domain\User\Entity\PersonalData;

interface PersonalDataRepositoryInterface
{
    public function findByBenutzerId(string $benutzerId): ?PersonalData;

    public function save(PersonalData $personalData): void;

    public function update(PersonalData $personalData): void;

    public function delete(PersonalData $personalData): void;
}

