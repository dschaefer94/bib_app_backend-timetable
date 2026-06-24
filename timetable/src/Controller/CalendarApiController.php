<?php

namespace App\Controller;

use App\Entity\Benutzer;
use App\Entity\StundenplanNeu;
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
    public function __construct(private EntityManagerInterface $entityManager)
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

        $entries = $this->entityManager
            ->getRepository(StundenplanNeu::class)
            ->findBy(['klasse' => $klasse->getClassName()]);

        $data = array_map(static function (StundenplanNeu $entry): array {
            return [
                'id' => (string) $entry->getId(),
                'summary' => $entry->getSummary(),
                'description' => $entry->getDescription(),
                'start' => $entry->getStart()->format(DATE_ATOM),
                'end' => $entry->getEnd()->format(DATE_ATOM),
                'location' => $entry->getLocation(),
                'label' => $entry->getLabel(),
                'kategorie' => $entry->getKategorie(),
                'updatedAt' => $entry->getUpdatedAt()?->format(DATE_ATOM),
            ];
        }, $entries);

        return new JsonResponse([
            'success' => true,
            'data' => $data,
            'timestamp' => (new \DateTimeImmutable())->format(DATE_ATOM),
        ], Response::HTTP_OK);
    }
}

