<?php

namespace App\Domain\Calendar\Service;

use App\Domain\Calendar\Entity\NotedChange;
use App\Domain\Calendar\Repository\NotedChangeRepositoryInterface;
use App\Domain\Calendar\DTO\CalendarEventDTO;
use App\Domain\Calendar\DTO\ChangeDTO;
use App\Domain\Calendar\DTO\NotedChangeDTO;
use App\Shared\Exception\EntityNotFoundException;
use App\Shared\Exception\ValidationException;
use Doctrine\DBAL\Connection;

class CalendarService
{
    public function __construct(
        private NotedChangeRepositoryInterface $notedChangeRepository,
        private Connection $connection
    ) {}

    /**
     * Holt den aktuellen Stundenplan einer Klasse
     */
    public function getCalendar(string $klassenname): array
    {
        try {
            $tableName = "{$klassenname}_neuer_stundenplan";
            $query = "SELECT * FROM `{$tableName}`";
            $result = $this->connection->executeQuery($query)->fetchAllAssociative();

            return array_map(
                fn(array $row) => CalendarEventDTO::fromArray($row)->toArray(),
                $result
            );
        } catch (\Exception $e) {
            throw new \Exception("Stundenplan für Klasse {$klassenname} konnte nicht geladen werden.", 500);
        }
    }

    /**
     * Holt alle Änderungen einer Klasse
     */
    public function getChanges(string $klassenname): array
    {
        try {
            $tableName = "{$klassenname}_aenderungen";
            $query = "SELECT * FROM `{$tableName}`";
            $result = $this->connection->executeQuery($query)->fetchAllAssociative();

            return array_map(
                fn(array $row) => ChangeDTO::fromArray($row)->toArray(),
                $result
            );
        } catch (\Exception $e) {
            throw new \Exception('Änderungen konnten nicht abgefragt werden.', 500);
        }
    }

    /**
     * Holt alle vom Benutzer gelesenen Änderungen
     */
    public function getNotedChanges(string $benutzerId): array
    {
        $notedChanges = $this->notedChangeRepository->findByBenutzerId($benutzerId);

        return array_map(
            fn(NotedChange $nc) => new NotedChangeDTO($nc->getTerminId())->toArray(),
            $notedChanges
        );
    }

    /**
     * Markiert einen Termin als gelesen
     */
    public function noteChange(string $benutzerId, string $terminId): array
    {
        if (empty($terminId)) {
            throw ValidationException::missingField('termin_id');
        }

        $notedChange = new NotedChange($benutzerId, $terminId);
        $this->notedChangeRepository->save($notedChange);

        return ['aenderungen_notiert' => true];
    }
}

