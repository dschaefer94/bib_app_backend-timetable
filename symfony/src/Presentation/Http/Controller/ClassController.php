<?php

namespace App\Presentation\Http\Controller;

use App\Application\Class\ClassApplicationService;
use App\Domain\Class\DTO\CreateClassDTO;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/classes", name="class_")
 * API für Klassenverwaltung
 *
 * HINWEIS: Diese Klasse wird durch den OpenAPI-Generator generiert.
 */
class ClassController extends AbstractController
{
    public function __construct(private ClassApplicationService $applicationService) {}

    /**
     * GET /api/classes
     * Alle Klassen abrufen
     */
    #[Route('', name: 'get_all', methods: ['GET'])]
    public function getClasses(): JsonResponse
    {
        try {
            $data = $this->applicationService->getAllClasses();
            return $this->json($data);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    /**
     * GET /api/classes/user
     * Klasse des aktuellen Benutzers abrufen
     */
    #[Route('/user', name: 'get_user_class', methods: ['GET'])]
    public function getUserClass(): JsonResponse
    {
        try {
            $data = $this->applicationService->getUserClass();
            return $this->json($data);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    /**
     * POST /api/classes
     * Neue Klasse erstellen
     */
    #[Route('', name: 'create', methods: ['POST'])]
    public function createClass(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $dto = CreateClassDTO::fromRequest($data);
            $result = $this->applicationService->createClass($dto);
            return $this->json($result, 201);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    /**
     * PUT /api/classes/{id}
     * Klasse aktualisieren
     */
    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function updateClass(int $id, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $dto = CreateClassDTO::fromRequest($data);
            $result = $this->applicationService->updateClass($id, $dto);
            return $this->json($result);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    /**
     * DELETE /api/classes/{id}
     * Klasse löschen
     */
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function deleteClass(int $id): JsonResponse
    {
        try {
            $result = $this->applicationService->deleteClass($id);
            return $this->json($result);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }
}

