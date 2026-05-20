<?php

namespace App\Application\Shared;

/**
 * Hält Informationen zum aktuellen Benutzer
 */
final class UserContext
{
    private ?string $userId = null;
    private bool $isAdmin = false;
    private ?string $klassenname = null;

    public function setUserId(string $userId): void
    {
        $this->userId = $userId;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function isAuthenticated(): bool
    {
        return $this->userId !== null;
    }

    public function setAdmin(bool $isAdmin): void
    {
        $this->isAdmin = $isAdmin;
    }

    public function isAdmin(): bool
    {
        return $this->isAdmin;
    }

    public function setKlassenname(string $klassenname): void
    {
        $this->klassenname = $klassenname;
    }

    public function getKlassenname(): ?string
    {
        return $this->klassenname;
    }
}

