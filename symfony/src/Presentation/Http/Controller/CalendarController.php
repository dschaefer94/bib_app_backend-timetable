<?php

namespace App\Presentation\Http\Controller;

use App\Application\Calendar\CalendarApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/calendar", name="calendar_")
 * API für Stundenplan- und Terminverwaltung
 *
 * HINWEIS: Diese Klasse wird durch den OpenAPI-Generator generiert.
 * Implementieren Sie die Logik in den angegebenen Methoden.
 */
class CalendarController extends AbstractController
{
    public function __construct(private CalendarApplicationService $applicationService) {}

    /**
     * GET /api/calendar
     * Aktuellen Stundenplan abrufen
     */
    #[Route('', name: 'get_calendar', methods: ['GET'])]
    public function getCalendar(): JsonResponse
    {
        try {
            $data = $this->applicationService->getCalendar();
            return $this->json($data);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    /**
     * GET /api/calendar/changes
     * Terminänderungen abrufen
     */
    #[Route('/changes', name: 'get_changes', methods: ['GET'])]
    public function getChanges(): JsonResponse
    {
        try {
            $data = $this->applicationService->getChanges();
            return $this->json($data);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    /**
     * GET /api/calendar/noted-changes
     * Gelesene Änderungen abrufen
     */
    #[Route('/noted-changes', name: 'get_noted_changes', methods: ['GET'])]
    public function getNotedChanges(): JsonResponse
    {
        try {
            $data = $this->applicationService->getNotedChanges();
            return $this->json($data);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    /**
     * POST /api/calendar/noted-changes
     * Terminänderung als gelesen markieren
     */
    #[Route('/noted-changes', name: 'post_noted_changes', methods: ['POST'])]
    public function noteChange(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $terminId = $data['termin_id'] ?? '';

            $result = $this->applicationService->noteChange($terminId);
            return $this->json($result);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }
}

