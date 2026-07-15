<?php

namespace App\Api;

use App\Entity\Benutzer; // Neu hinzugefügt
use App\Entity\GeaenderteTermine;
use App\Entity\PersoenlicheDaten; // Neu hinzugefügt
use App\Entity\StundenplanNeu;
use App\OpenApi\Api\CalendarApiInterface;
use App\OpenApi\Model\CalendarEvent;
use App\OpenApi\Model\CalendarEventOriginalEvent;
use App\OpenApi\Model\GetCalendar200Response;
use App\Service\CalendarEventNormalizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Uid\Uuid;

class CalendarApiService implements CalendarApiInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CalendarEventNormalizer $eventNormalizer,
        private ?Security $security = null
    ) {}

    /**
     * @inheritDoc
     */
    public function setBearerAuth(?string $value): void
    {
        // Diese Methode ist für die Server-Implementierung in der Regel nicht relevant.
        // Die Authentifizierung wird vom Symfony Security System gehandhabt.
        // Sie muss hier implementiert werden, da sie Teil des generierten Interfaces ist.
    }

    /**
     * @inheritDoc
     */
    public function getCalendar(int &$responseCode, array &$responseHeaders): array|object|null
    {
        $dummyUser = null;
        $authUser = $this->security?->getUser();
        if ($authUser instanceof Benutzer) {
            $dummyUser = $authUser;
        } else {
            $userRepo = $this->entityManager->getRepository(Benutzer::class);
            if ($userRepo && method_exists($userRepo, 'findOneBy')) {
                $dummyUser = $userRepo->findOneBy(['email' => 'dummyuser@example.com']);
            }
        }

        $persoenlicheDaten = $dummyUser ? $dummyUser->getPersoenlicheDaten() : null;

        if (!$persoenlicheDaten) {
            $persRepo = $this->entityManager->getRepository(PersoenlicheDaten::class);
            if ($persRepo && method_exists($persRepo, 'findOneBy')) {
                try {
                    $dummyUserUuid = Uuid::fromString('550e8400-e29b-41d4-a716-446655440000');
                    $persoenlicheDaten = $persRepo->findOneBy(['benutzer' => $dummyUserUuid]);
                    if ($persoenlicheDaten && method_exists($persoenlicheDaten, 'getBenutzer')) {
                        $dummyUser = $persoenlicheDaten->getBenutzer();
                    }
                } catch (\InvalidArgumentException $e) {
                }
            }
        }

        if (!$persoenlicheDaten || !$persoenlicheDaten->getKlasse()) {
            $responseCode = 404;
            return new \App\OpenApi\Model\Problem([
                'type' => '/problems/calendar-not-found',
                'title' => 'Calendar not found',
                'status' => 404,
                'detail' => 'No calendar could be resolved for the current authenticated user context.',
                'instance' => '/api/calendar#missing-context'
            ]);
        }

        $klasse = $persoenlicheDaten->getKlasse()->getClassName();

        $entries = [];
        $entriesRepo = $this->entityManager->getRepository(StundenplanNeu::class);
        if ($entriesRepo && method_exists($entriesRepo, 'findBy')) {
            $entriesResult = $entriesRepo->findBy(['klasse' => $klasse]);
            $entries = is_array($entriesResult) ? $entriesResult : [];
        }

        $events = [];
        foreach ($entries as $entry) {
            if (!$entry instanceof StundenplanNeu) {
                continue;
            }

            $event = new CalendarEvent();
            $event->setId((string) $entry->getId());
            $event->setSummary($entry->getSummary());
            $event->setDescription($entry->getDescription());
            $event->setStart(\DateTime::createFromImmutable($entry->getStart()));
            $event->setEnd(\DateTime::createFromImmutable($entry->getEnd()));
            $event->setLocation($entry->getLocation());
            $event->setLabel($this->eventNormalizer->normalizeLabel($entry->getLabel()));
            $event->setCategory($this->eventNormalizer->normalizeCategory($entry->getKategorie()));

            $normalizedOriginal = $this->eventNormalizer->normalizeOriginalEvent($entry->getOriginalEvent());
            $origDto = $this->buildOriginalEventDto($normalizedOriginal);
            if ($origDto !== null) {
                $event->setOriginalEvent($origDto);
            }

            $event->setUpdatedAt($entry->getUpdatedAt() ? \DateTime::createFromImmutable($entry->getUpdatedAt()) : null);
            $events[] = $event;
        }

        $changesRepo = $this->entityManager->getRepository(GeaenderteTermine::class);
        if ($changesRepo && method_exists($changesRepo, 'findBy')) {
            $changeEntries = $changesRepo->findBy(['klasse' => $klasse], ['updatedAt' => 'DESC']);
            if (is_array($changeEntries)) {
                foreach ($changeEntries as $change) {
                    if (!$change instanceof GeaenderteTermine) {
                        continue;
                    }

                    $event = new CalendarEvent();
                    $event->setId((string) $change->getId());
                    $event->setSummary($change->getSummary());
                    $event->setDescription($change->getDescription());
                    $event->setStart(\DateTime::createFromImmutable($change->getStart()));
                    $event->setEnd(\DateTime::createFromImmutable($change->getEnd()));
                    $event->setLocation($change->getLocation());
                    $event->setLabel($this->eventNormalizer->mapChangeTypeToLabel($change->getChangeType()) ?? $this->eventNormalizer->normalizeLabel($change->getLabel()));
                    $event->setCategory($this->eventNormalizer->normalizeCategory($change->getKategorie()));
                    $event->setUpdatedAt($change->getUpdatedAt() ? \DateTime::createFromImmutable($change->getUpdatedAt()) : null);

                    $normalizedOriginal = $this->eventNormalizer->normalizeOriginalEvent($change->getOriginalEvent());
                    $origDto = $this->buildOriginalEventDto($normalizedOriginal);
                    if ($origDto !== null) {
                        $event->setOriginalEvent($origDto);
                    }

                    $events[] = $event;
                }
            }
        }

        usort($events, static function (CalendarEvent $left, CalendarEvent $right): int {
            $leftKey = sprintf(
                '%s|%s|%s|%s',
                $left->getStart()?->format(DATE_ATOM) ?? '',
                $left->getEnd()?->format(DATE_ATOM) ?? '',
                $left->getSummary() ?? '',
                $left->getId() ?? ''
            );
            $rightKey = sprintf(
                '%s|%s|%s|%s',
                $right->getStart()?->format(DATE_ATOM) ?? '',
                $right->getEnd()?->format(DATE_ATOM) ?? '',
                $right->getSummary() ?? '',
                $right->getId() ?? ''
            );

            return $leftKey <=> $rightKey;
        });

        $responseCode = 200;

        return new GetCalendar200Response([
            'timestamp' => new \DateTime(),
            'events' => $events
        ]);
    }

    private function buildOriginalEventDto(?array $originalEvent): ?CalendarEventOriginalEvent
    {
        if (!is_array($originalEvent)) {
            return null;
        }
        if (!isset($originalEvent['summary'], $originalEvent['start'], $originalEvent['end'])) {
            return null;
        }

        try {
            $start = new \DateTime((string) $originalEvent['start']);
            $end = new \DateTime((string) $originalEvent['end']);
        } catch (\Exception) {
            return null;
        }

        $originalEventDto = new CalendarEventOriginalEvent();
        $originalEventDto->setSummary((string) $originalEvent['summary']);
        $originalEventDto->setStart($start);
        $originalEventDto->setEnd($end);
        if (isset($originalEvent['location'])) {
            $originalEventDto->setLocation((string) $originalEvent['location']);
        }

        return $originalEventDto;
    }
}
