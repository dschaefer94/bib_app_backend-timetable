<?php

namespace App\Controller;

use App\Entity\Benutzer;
use App\Entity\CalendarSource;
use App\Entity\PersoenlicheDaten;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

#[Route('/api/profile')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class UserProfileApiController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/me', name: 'app_profile_get_user_profile', methods: ['GET'])]
    public function getUserProfile(): JsonResponse {
        /** @var Benutzer $user */
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse([
                'type' => '/problems/authentication-failed',
                'title' => 'Authentication Failed',
                'status' => Response::HTTP_UNAUTHORIZED,
                'detail' => 'User not found or not authenticated.',
                'instance' => '/api/profile/me',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $persoenlicheDaten = $user->getPersoenlicheDaten();
        $klasse = $persoenlicheDaten ? $persoenlicheDaten->getKlasse() : null;

        return new JsonResponse([
            'identityId' => $user->getUserIdentifier(),
            'email' => $user->getEmail(),
            'isAdmin' => $user->isIstadmin(),
            'roles' => $user->getRoles(),
            'klasse' => $klasse ? [
                'id' => (string) $klasse->getId(),
                'name' => $klasse->getClassName(),
            ] : null,
        ], Response::HTTP_OK);
    }

    #[Route('/update-klasse', name: 'app_profile_update_klasse', methods: ['POST'])]
    public function updateKlasse(Request $request): JsonResponse {
        /** @var Benutzer $user */
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['message' => 'User not found or not authenticated.'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        $klassenId = $data['klassen_id'] ?? null;

        if (!$klassenId) {
            return new JsonResponse(['message' => 'Missing "klassen_id" in request body.'], Response::HTTP_BAD_REQUEST);
        }

        $klasse = $this->entityManager->getRepository(CalendarSource::class)->find($klassenId);

        if (!$klasse) {
            return new JsonResponse(['message' => sprintf('Klasse with ID "%s" not found.', $klassenId)], Response::HTTP_NOT_FOUND);
        }

        $persoenlicheDaten = $user->getPersoenlicheDaten();
        if (!$persoenlicheDaten) {
            $persoenlicheDaten = new PersoenlicheDaten();
            $persoenlicheDaten->setBenutzer($user);
            // TODO: Set name and vorname from somewhere, e.g., from JWT claims or another API call
            $persoenlicheDaten->setName('Default Name'); // Placeholder
            $persoenlicheDaten->setVorname('Default Vorname'); // Placeholder
            $this->entityManager->persist($persoenlicheDaten);

            $user->setPersoenlicheDaten($persoenlicheDaten);
        }

        $persoenlicheDaten->setKlasse($klasse);
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Klasse updated successfully.',
            'klassen_id' => (string) $klasse->getId(),
        ], Response::HTTP_OK);
    }
}
