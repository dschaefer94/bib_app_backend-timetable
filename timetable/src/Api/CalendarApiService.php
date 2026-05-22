<?php

namespace App\Api;

use App\Entity\CalendarEntry;
use App\OpenApi\Api\CalendarApiInterface;
use App\OpenApi\Model\CalendarEvent;
use App\OpenApi\Model\CalendarEventOriginalEvent;
use App\OpenApi\Model\GetCalendar200Response;
use Doctrine\ORM\EntityManagerInterface;

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
        $entries = $this->entityManager->getRepository(CalendarEntry::class)->findAll();

        $events = [];
        foreach ($entries as $entry) {
            $event = new CalendarEvent();
            $event->setId((string) $entry->getId());
            $event->setSummary($entry->getSummary());
            $event->setDescription($entry->getDescription());
            $event->setStart($entry->getStart());
            $event->setEnd($entry->getEnd());
            $event->setLocation($entry->getLocation());
            $event->setLabel($entry->getLabel());
            $event->setKategorie($entry->getKategorie());

            if ($orig = $entry->getOriginalEvent()) {
                // Nur mappen wenn mindestens summary, start, end vorhanden (laut OpenAPI required)
                if (isset($orig['summary'], $orig['start'], $orig['end'])) {
                    $origDto = new CalendarEventOriginalEvent();
                    $origDto->setSummary($orig['summary']);
                    $origDto->setStart(new \DateTime($orig['start']));
                    $origDto->setEnd(new \DateTime($orig['end']));
                    $origDto->setLocation($orig['location'] ?? null);
                    $event->setOriginalEvent($origDto);
                }
            }

            $event->setUpdatedAt($entry->getUpdatedAt());
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
