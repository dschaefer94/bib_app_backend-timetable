<?php

namespace App\Controller;

use App\Entity\Benutzer;
use App\Entity\Klassen;
use App\Entity\PersoenlicheDaten;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/profile')]
#[IsGranted('IS_AUTHENTICATED_FULLY')] // Stellt sicher, dass nur authentifizierte Benutzer zugreifen können
class UserProfileController extends AbstractController
{
    #[Route('/update-klasse', name: 'app_profile_update_klasse', methods: ['POST'])]
    public function updateKlasse(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
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

        // Finde die Klasse anhand der ID
        $klasse = $entityManager->getRepository(Klassen::class)->find($klassenId);

        if (!$klasse) {
            return new JsonResponse(['message' => sprintf('Klasse with ID "%s" not found.', $klassenId)], Response::HTTP_NOT_FOUND);
        }

        // Lade oder erstelle die persönlichen Daten des Benutzers
        $persoenlicheDaten = $user->getPersoenlicheDaten();
        if (!$persoenlicheDaten) {
            // Wenn keine persönlichen Daten existieren, erstelle neue.
            // In einem realen Szenario müssten hier weitere Daten (Name, Vorname) gesetzt werden.
            // Für dieses Beispiel setzen wir nur die Klasse.
            $persoenlicheDaten = new PersoenlicheDaten();
            $persoenlicheDaten->setBenutzer($user);
            // TODO: Set name and vorname from somewhere, e.g., from JWT claims or another API call
            $persoenlicheDaten->setName('Default Name'); // Placeholder
            $persoenlicheDaten->setVorname('Default Vorname'); // Placeholder
            $entityManager->persist($persoenlicheDaten);
            $user->setPersoenlicheDaten($persoenlicheDaten);
        }

        $persoenlicheDaten->setKlassen($klasse);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Klasse updated successfully.', 'klasse_id' => $klasse->getKlassenId()]);
    }

    #[Route('/me', name: 'app_profile_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        /** @var Benutzer $user */
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['message' => 'User not found or not authenticated.'], Response::HTTP_UNAUTHORIZED);
        }

        $persoenlicheDaten = $user->getPersoenlicheDaten();
        $klasse = $persoenlicheDaten ? $persoenlicheDaten->getKlassen() : null;

        return new JsonResponse([
            'identity_id' => $user->getUserIdentifier(),
            'email' => $user->getEmail(),
            'is_admin' => $user->isIstadmin(),
            'roles' => $user->getRoles(),
            'klasse' => $klasse ? [
                'id' => $klasse->getKlassenId(),
                'name' => $klasse->getKlassenname(),
            ] : null,
            // Weitere Benutzerdaten hier hinzufügen
        ]);
    }
}
