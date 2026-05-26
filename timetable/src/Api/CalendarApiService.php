<?php

namespace App\Api;

use App\Entity\StundenplanNeu;
use App\Entity\Benutzer; // Neu hinzugefügt
use App\Entity\PersoenlicheDaten; // Neu hinzugefügt
use App\Entity\CalendarSource; // Neu hinzugefügt
use App\OpenApi\Api\CalendarApiInterface;
use App\OpenApi\Model\CalendarEvent;
use App\OpenApi\Model\CalendarEventOriginalEvent;
use App\OpenApi\Model\GetCalendar200Response;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid; // Neu hinzugefügt

class CalendarApiService implements CalendarApiInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * @inheritDoc
     */
    public function getCalendar(int &$responseCode, array &$responseHeaders): array|object|null
    {
        // TODO: Sobald die Authentifizierung implementiert ist, sollte die Benutzer-ID
        // aus dem Sicherheitstoken oder der Session des aktuell angemeldeten Benutzers kommen.
        // Für den Moment verwenden wir die fest verdrahtete ID des Dummyusers aus den Fixtures.
        $dummyUserUuid = Uuid::fromString('550e8400-e29b-41d4-a716-446655440000');

        // 1. Persönliche Daten des Benutzers abrufen
        $persoenlicheDaten = $this->entityManager->getRepository(PersoenlicheDaten::class)->findOneBy(['benutzer' => $dummyUserUuid]);

        if (!$persoenlicheDaten || !$persoenlicheDaten->getKlasse()) {
            // Wenn keine persönlichen Daten oder keine Klasse gefunden wurde,
            // können wir einen Fehler zurückgeben oder einen leeren Stundenplan.
            // Hier geben wir einen Fehler zurück, wie in der OpenAPI-Spezifikation angedeutet.
            $responseCode = 404; // Oder 400, je nach gewünschtem Verhalten
            return new \App\OpenApi\Model\Problem([
                'type' => '/problems/calendar-not-found',
                'title' => 'Calendar not found',
                'status' => 404,
                'detail' => 'No calendar could be resolved for the current user context (dummyuser).',
                'instance' => '/api/calendar#missing-context'
            ]);
        }

        // 2. Klassennamen aus den persönlichen Daten extrahieren
        $klasse = $persoenlicheDaten->getKlasse()->getClassName();

        // 3. Stundenplan-Einträge für die ermittelte Klasse abrufen
        $entries = $this->entityManager->getRepository(StundenplanNeu::class)->findBy(['klasse' => $klasse]);

        $events = [];
        foreach ($entries as $entry) {
            $event = new CalendarEvent();
            $event->setId((string) $entry->getId());
            $event->setSummary($entry->getSummary());
            $event->setDescription($entry->getDescription());
            $event->setStart(\DateTime::createFromImmutable($entry->getStart())); // Konvertiert zu DateTime
            $event->setEnd(\DateTime::createFromImmutable($entry->getEnd()));     // Konvertiert zu DateTime
            $event->setLocation($entry->getLocation());
            $event->setLabel($entry->getLabel());
            $event->setKategorie($entry->getKategorie());

            if ($orig = $entry->getOriginalEvent()) {
                // Nur mappen wenn mindestens summary, start, end vorhanden (laut OpenAPI required)
                if (isset($orig['summary'], $orig['start'], $orig['end'])) {
                    $origDto = new CalendarEventOriginalEvent();
                    $origDto->setSummary($orig['summary']);
                    // DateTimeImmutable muss in DateTime konvertiert werden, wenn das OpenAPI-Modell DateTime erwartet
                    $origDto->setStart(\DateTime::createFromImmutable($orig['start']));
                    $origDto->setEnd(\DateTime::createFromImmutable($orig['end']));
                    $origDto->setLocation($orig['location'] ?? null);
                    $event->setOriginalEvent($origDto);
                }
            }

            // DateTimeImmutable muss in DateTime konvertiert werden, wenn das OpenAPI-Modell DateTime erwartet
            $event->setUpdatedAt($entry->getUpdatedAt() ? \DateTime::createFromImmutable($entry->getUpdatedAt()) : null);
            $events[] = $event;
        }

        $responseCode = 200;

        return new GetCalendar200Response([
            'success' => true,
            'data' => $events,
            'timestamp' => new \DateTime()
        ]);
    }
}
