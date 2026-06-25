<?php

namespace App\Service;

use App\Entity\CalendarSource;
use App\Entity\StundenplanNeu;
use App\Entity\StundenplanAlt;
use App\Entity\GeaenderteTermine;
use Doctrine\ORM\EntityManagerInterface;
use Sabre\VObject\Reader;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CalendarSyncService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private HttpClientInterface $httpClient
    ) {}

    public function syncAll(): void
    {
        $sources = $this->entityManager->getRepository(CalendarSource::class)->findAll();
        foreach ($sources as $source) {
            $this->syncSource($source);
        }
    }

    public function syncSource(CalendarSource $source): void
    {
        $klasse = $source->getClassName();
        if (empty($klasse)) {
            // Loggen oder Fehler werfen, wenn keine Klasse zugewiesen ist
            return;
        }

        // 1. Aktuellen Stundenplan (StundenplanNeu) in StundenplanAlt verschieben
        $this->moveStundenplanNeuToAlt($klasse);

        // 2. StundenplanNeu für diese Klasse leeren
        $this->clearStundenplanNeu($klasse);

        // 3. iCal-Feed abrufen und neuen Stundenplan (StundenplanNeu) füllen
        $response = $this->httpClient->request('GET', $source->getIcalLink());
        $vcalendar = Reader::read($response->getContent());

        $newEvents = [];
        foreach ($vcalendar->VEVENT as $vevent) {
            $uid = (string)$vevent->UID;
            $newEvent = new StundenplanNeu();
            $newEvent->setSummary((string)$vevent->SUMMARY);
            $newEvent->setDescription((string)$vevent->DESCRIPTION);
            $newEvent->setStart(new \DateTimeImmutable($vevent->DTSTART->getDateTime()->format('Y-m-d H:i:s')));
            $newEvent->setEnd(new \DateTimeImmutable($vevent->DTEND->getDateTime()->format('Y-m-d H:i:s')));
            $newEvent->setLocation((string)$vevent->LOCATION);
            $newEvent->setOriginalEvent(['uid' => $uid]);
            $newEvent->setKlasse($klasse);

            $this->entityManager->persist($newEvent);
            $newEvents[$uid] = $newEvent;
        }
        $this->entityManager->flush(); // Flush, um IDs zu generieren und die neuen Events zu speichern

        // 4. Änderungen identifizieren und in GeaenderteTermine speichern
        $this->identifyAndStoreChanges($klasse, $newEvents);

        // 5. Letzte Synchronisationszeit aktualisieren
        $source->setLastSyncedAt(new \DateTime());
        $this->entityManager->flush();
    }

    private function moveStundenplanNeuToAlt(string $klasse): void
    {
        $stundenplanNeuRepository = $this->entityManager->getRepository(StundenplanNeu::class);
        $currentNeuEvents = $stundenplanNeuRepository->findBy(['klasse' => $klasse]);

        foreach ($currentNeuEvents as $neuEvent) {
            $altEvent = new StundenplanAlt();
            $altEvent->setSummary($neuEvent->getSummary());
            $altEvent->setDescription($neuEvent->getDescription());
            $altEvent->setStart($neuEvent->getStart());
            $altEvent->setEnd($neuEvent->getEnd());
            $altEvent->setLocation($neuEvent->getLocation());
            $altEvent->setLabel($neuEvent->getLabel());
            $altEvent->setKategorie($neuEvent->getKategorie());
            $altEvent->setOriginalEvent($neuEvent->getOriginalEvent());
            $altEvent->setUpdatedAt($neuEvent->getUpdatedAt());
            $altEvent->setKlasse($neuEvent->getKlasse());

            $this->entityManager->persist($altEvent);
        }
        $this->entityManager->flush();
    }

    private function clearStundenplanNeu(string $klasse): void
    {
        $stundenplanNeuRepository = $this->entityManager->getRepository(StundenplanNeu::class);
        $qb = $stundenplanNeuRepository->createQueryBuilder('sn');
        $qb->delete()
           ->where('sn.klasse = :klasse')
           ->setParameter('klasse', $klasse)
           ->getQuery()
           ->execute();
    }

    private function identifyAndStoreChanges(string $klasse, array $newEvents): void
    {
        $stundenplanAltRepository = $this->entityManager->getRepository(StundenplanAlt::class);
        $oldEvents = $stundenplanAltRepository->findBy(['klasse' => $klasse]);

        if (empty($oldEvents)) {
            return;
        }

        $oldEventsMap = [];
        foreach ($oldEvents as $event) {
            $oldEventsMap[$this->getEventKey($event)] = $event;
        }

        // Gelöschte Termine
        foreach ($oldEventsMap as $uid => $oldEvent) {
            if (!isset($newEvents[$uid])) {
                $this->logChange($oldEvent, 'gelöscht');
            }
        }

        // Hinzugefügte und geänderte Termine
        foreach ($newEvents as $uid => $newEvent) {
            if (!isset($oldEventsMap[$uid])) {
                $this->logChange($newEvent, 'neu');
            } else {
                $oldEvent = $oldEventsMap[$uid];
                if ($this->hasEventChanged($oldEvent, $newEvent)) {
                    $this->logChange($newEvent, 'geändert');
                }
            }
        }
    }

    private function hasEventChanged(StundenplanAlt $oldEvent, StundenplanNeu $newEvent): bool
    {
        // Implementiere hier die Logik, um zu prüfen, ob sich ein Event geändert hat.
        // Vergleiche relevante Felder wie summary, start, end, location, etc.
        // Beispiel:
        if ($oldEvent->getSummary() !== $newEvent->getSummary() ||
            $oldEvent->getDescription() !== $newEvent->getDescription() ||
            $oldEvent->getStart() != $newEvent->getStart() || // DateTimeImmutable Objekte können direkt verglichen werden
            $oldEvent->getEnd() != $newEvent->getEnd() ||
            $oldEvent->getLocation() !== $newEvent->getLocation()) {
            return true;
        }
        // Füge hier weitere Vergleiche für andere Felder hinzu
        return false;
    }

    private function logChange($event, string $changeType): void
    {
        $geaenderterTermin = new GeaenderteTermine();
        $geaenderterTermin->setSummary($event->getSummary());
        $geaenderterTermin->setDescription($event->getDescription());
        $geaenderterTermin->setStart($event->getStart());
        $geaenderterTermin->setEnd($event->getEnd());
        $geaenderterTermin->setLocation($event->getLocation());
        $geaenderterTermin->setLabel($event->getLabel());
        $geaenderterTermin->setKategorie($event->getKategorie());
        $geaenderterTermin->setOriginalEvent($event->getOriginalEvent());
        $geaenderterTermin->setUpdatedAt(new \DateTimeImmutable()); // Zeitpunkt der Änderung
        $geaenderterTermin->setKlasse($event->getKlasse());
        $geaenderterTermin->setChangeType($changeType);

        $this->entityManager->persist($geaenderterTermin);
        $this->entityManager->flush(); // Flush hier, um Änderungen sofort zu protokollieren
    }

    private function getEventKey(object $event): string
    {
        $originalEvent = method_exists($event, 'getOriginalEvent') ? $event->getOriginalEvent() : null;
        if (is_array($originalEvent) && !empty($originalEvent['uid'])) {
            return (string) $originalEvent['uid'];
        }

        return method_exists($event, 'getId') && $event->getId() ? (string) $event->getId() : spl_object_hash($event);
    }
}
