<?php

namespace App\Controller;

use App\Entity\Benutzer;
use App\Entity\GeaenderteTermine;
use App\Entity\StundenplanNeu;
use App\Service\CalendarEventNormalizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/calendar')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class CalendarApiController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CalendarEventNormalizer $eventNormalizer
    )
    {
    }

    #[Route('', name: 'app_calendar_get', methods: ['GET'])]
    public function getCalendar(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof Benutzer) {
            return new JsonResponse([
                'type' => '/problems/authentication-failed',
                'title' => 'Authentication Failed',
                'status' => Response::HTTP_UNAUTHORIZED,
                'detail' => 'User not found or not authenticated.',
                'instance' => '/api/calendar',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $persoenlicheDaten = $user->getPersoenlicheDaten();
        $klasse = $persoenlicheDaten?->getKlasse();

        if (!$klasse) {
            return new JsonResponse([
                'type' => '/problems/calendar-not-found',
                'title' => 'Calendar not found',
                'status' => Response::HTTP_NOT_FOUND,
                'detail' => 'No class assigned for current user.',
                'instance' => '/api/calendar',
            ], Response::HTTP_NOT_FOUND);
        }

        $className = $klasse->getClassName();
        $classId = $klasse->getId();

        // Legacy fallback: events directly assigned via `stundenplan_neu.klasse` string
        $entries = $this->entityManager
            ->getRepository(StundenplanNeu::class)
            ->findBy(['klasse' => $className]);

        // Preferred mapping: many-to-many via join table stundenplan_neu_klasse
        $mappedEntries = [];
        if ($classId !== null) {
            $mappedIds = $this->entityManager->getConnection()->fetchFirstColumn(
                'SELECT stundenplan_neu_id FROM stundenplan_neu_klasse WHERE klassen_id = :id',
                ['id' => $classId]
            );

            if (!empty($mappedIds)) {
                $mappedEntries = $this->entityManager
                    ->getRepository(StundenplanNeu::class)
                    ->findBy(['id' => $mappedIds]);
            }
        }

        $mergedById = [];
        foreach (array_merge($entries, $mappedEntries) as $entry) {
            $mergedById[(string) $entry->getId()] = $entry;
        }
        $entries = array_values($mergedById);

        $data = array_map(function (StundenplanNeu $entry): array {
            return array_filter([
                'id' => (string) $entry->getId(),
                'summary' => $entry->getSummary(),
                'description' => $entry->getDescription(),
                'start' => $entry->getStart()->format(DATE_ATOM),
                'end' => $entry->getEnd()->format(DATE_ATOM),
                'location' => $entry->getLocation(),
                'label' => $this->eventNormalizer->normalizeLabel($entry->getLabel()),
                'category' => $this->eventNormalizer->normalizeCategory($entry->getKategorie()),
                'originalEvent' => $this->eventNormalizer->normalizeOriginalEvent($entry->getOriginalEvent()),
                'updatedAt' => $entry->getUpdatedAt()?->format(DATE_ATOM),
            ], static fn (mixed $value): bool => $value !== null);
        }, $entries);

        // Include change history entries for the class with original event payload.
        $changeEntries = $this->entityManager
            ->getRepository(GeaenderteTermine::class)
            ->findBy(['klasse' => $className], ['updatedAt' => 'DESC']);

        foreach ($changeEntries as $change) {
            $data[] = array_filter([
                'id' => (string) $change->getId(),
                'summary' => $change->getSummary(),
                'description' => $change->getDescription(),
                'start' => $change->getStart()->format(DATE_ATOM),
                'end' => $change->getEnd()->format(DATE_ATOM),
                'location' => $change->getLocation(),
                'label' => $this->eventNormalizer->mapChangeTypeToLabel($change->getChangeType()) ?? $this->eventNormalizer->normalizeLabel($change->getLabel()),
                'category' => $this->eventNormalizer->normalizeCategory($change->getKategorie()),
                'originalEvent' => $this->eventNormalizer->normalizeOriginalEvent($change->getOriginalEvent()),
                'updatedAt' => $change->getUpdatedAt()?->format(DATE_ATOM),
            ], static fn (mixed $value): bool => $value !== null);
        }

        $this->eventNormalizer->sortEvents($data);

        return new JsonResponse([
            'timestamp' => (new \DateTimeImmutable())->format(DATE_ATOM),
            'events' => $data,
        ], Response::HTTP_OK);
    }
}
