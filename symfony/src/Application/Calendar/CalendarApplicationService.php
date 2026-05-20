<?php

namespace App\Application\Calendar;

use App\Domain\Calendar\Service\CalendarService;
use App\Application\Shared\UserContext;
use App\Shared\Exception\UnauthorizedException;

/**
 * Application Service für Calendar Use Cases
 * Orchestriert die Geschäftslogik auf Anwendungsebene
 */
class CalendarApplicationService
{
    public function __construct(
        private CalendarService $calendarService,
        private UserContext $userContext
    ) {}

    /**
     * Use Case: Stundenplan abrufen
     */
    public function getCalendar(): array
    {
        if (!$this->userContext->isAuthenticated()) {
            throw UnauthorizedException::notLoggedIn();
        }

        $klassenname = $this->userContext->getKlassenname();
        if (!$klassenname) {
            throw UnauthorizedException::notLoggedIn();
        }

        return $this->calendarService->getCalendar($klassenname);
    }

    /**
     * Use Case: Änderungen abrufen
     */
    public function getChanges(): array
    {
        if (!$this->userContext->isAuthenticated()) {
            throw UnauthorizedException::notLoggedIn();
        }

        $klassenname = $this->userContext->getKlassenname();
        if (!$klassenname) {
            throw UnauthorizedException::notLoggedIn();
        }

        return $this->calendarService->getChanges($klassenname);
    }

    /**
     * Use Case: Gelesene Änderungen abrufen
     */
    public function getNotedChanges(): array
    {
        if (!$this->userContext->isAuthenticated()) {
            throw UnauthorizedException::notLoggedIn();
        }

        return $this->calendarService->getNotedChanges($this->userContext->getUserId());
    }

    /**
     * Use Case: Änderung als gelesen markieren
     */
    public function noteChange(string $terminId): array
    {
        if (!$this->userContext->isAuthenticated()) {
            throw UnauthorizedException::notLoggedIn();
        }

        return $this->calendarService->noteChange($this->userContext->getUserId(), $terminId);
    }
}

