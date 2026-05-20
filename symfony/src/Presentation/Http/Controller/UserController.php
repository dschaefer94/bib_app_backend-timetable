<?php

namespace App\Presentation\Http\Controller;

use App\Application\User\UserApplicationService;
use App\Application\User\PasswordApplicationService;
use App\Domain\User\DTO\RegisterUserDTO;
use App\Domain\User\DTO\UpdateProfileDTO;
use App\Domain\Password\DTO\RequestPasswordResetDTO;
use App\Domain\Password\DTO\ResetPasswordDTO;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/users", name="user_")
 * API für Benutzerverwaltung
 *
 * HINWEIS: Diese Klasse wird durch den OpenAPI-Generator generiert.
 */
class UserController extends AbstractController
{
    public function __construct(
        private UserApplicationService $userApplicationService,
        private PasswordApplicationService $passwordApplicationService
    ) {}

    /**
     * GET /api/users/me
     * Aktuelle Benutzer-Daten abrufen
     */
    #[Route('/me', name: 'get_current', methods: ['GET'])]
    public function getUser(): JsonResponse
    {
        try {
            $data = $this->userApplicationService->getUser();
            return $this->json($data);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    /**
     * POST /api/users/register
     * Neuen Benutzer registrieren
     */
    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $dto = RegisterUserDTO::fromRequest($data);
            $result = $this->userApplicationService->registerUser($dto);
            return $this->json($result, 201);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    /**
     * GET /api/users/profile
     * Benutzerprofil abrufen
     */
    #[Route('/profile', name: 'get_profile', methods: ['GET'])]
    public function getProfile(): JsonResponse
    {
        try {
            $data = $this->userApplicationService->getProfile();
            return $this->json($data);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    /**
     * PUT /api/users/profile
     * Benutzerprofil aktualisieren
     */
    #[Route('/profile', name: 'update_profile', methods: ['PUT'])]
    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $dto = UpdateProfileDTO::fromRequest($data);
            $result = $this->userApplicationService->updateProfile($dto);
            return $this->json($result);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    /**
     * POST /api/password/reset-request
     * Passwort-Reset anfordern
     */
    #[Route('/password/reset-request', name: 'request_password_reset', methods: ['POST'])]
    public function requestPasswordReset(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $dto = RequestPasswordResetDTO::fromRequest($data);
            $result = $this->passwordApplicationService->requestPasswordReset($dto);
            return $this->json($result);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    /**
     * POST /api/password/reset
     * Passwort zurücksetzen
     */
    #[Route('/password/reset', name: 'reset_password', methods: ['POST'])]
    public function resetPassword(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $dto = ResetPasswordDTO::fromRequest($data);
            $result = $this->passwordApplicationService->resetPassword($dto);
            return $this->json($result);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }
}

