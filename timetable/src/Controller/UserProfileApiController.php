<?php

namespace App\Controller;

use App\OpenApi\Api\UserProfileApiInterface;
use App\OpenApi\Model\UserProfile; // Angenommen, dein Generator erstellt diese DTOs
use App\OpenApi\Model\UpdateKlasseRequest;
use App\OpenApi\Model\UpdateKlasse200Response; // Angenommen, dies ist das DTO für die Erfolgsantwort
use App\Entity\Benutzer;
use App\Entity\Klassen;
use App\Entity\PersoenlicheDaten;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException; // Für generische Fehler
use Symfony\Component\HttpFoundation\JsonResponse; // Für die Rückgabe der JsonResponse im Erfolgsfall

#[Route('/api/profile')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class UserProfileApiController extends AbstractController implements UserProfileApiInterface
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @inheritDoc
     */
    public function setBearerAuth(?string $value): void
    {
        // Diese Methode ist für die Server-Implementierung in der Regel nicht relevant.
        // Die Authentifizierung wird vom Symfony Security System gehandhabt.
        // Du kannst hier eine Exception werfen, wenn du sicherstellen möchtest, dass sie nicht aufgerufen wird.
        // throw new \BadMethodCallException('Method not implemented for server-side API.');
    }

    /**
     * @inheritDoc
     */
    #[Route('/me', name: 'app_profile_get_user_profile', methods: ['GET'])]
    public function getUserProfile(
        int &$responseCode,
        array &$responseHeaders
    ): array|object|null {
        /** @var Benutzer $user */
        $user = $this->getUser();

        if (!$user) {
            // Dies sollte durch #[IsGranted] abgefangen werden, aber als Fallback
            $responseCode = Response::HTTP_UNAUTHORIZED;
            throw new UnauthorizedHttpException('Bearer', 'User not found or not authenticated.');
        }

        $persoenlicheDaten = $user->getPersoenlicheDaten();
        $klasse = $persoenlicheDaten ? $persoenlicheDaten->getKlassen() : null;

        // DTO erstellen und befüllen
        $userProfile = new UserProfile();
        $userProfile->setIdentityId($user->getUserIdentifier());
        $userProfile->setEmail($user->getEmail());
        $userProfile->setIsAdmin($user->isIstadmin());
        $userProfile->setRoles($user->getRoles());

        if ($klasse) {
            $klasseDto = new \App\OpenApi\Model\Klasse(); // Angenommen, dieser DTO wird auch generiert
            $klasseDto->setId($klasse->getKlassenId());
            $klasseDto->setName($klasse->getKlassenname());
            $userProfile->setKlasse($klasseDto);
        }

        $responseCode = Response::HTTP_OK;
        // $responseHeaders können hier gesetzt werden, falls nötig
        return $userProfile;
    }

    /**
     * @inheritDoc
     */
    #[Route('/update-klasse', name: 'app_profile_update_klasse', methods: ['POST'])]
    public function updateKlasse(
        UpdateKlasseRequest $updateKlasseRequest,
        int &$responseCode,
        array &$responseHeaders
    ): array|object|null {
        /** @var Benutzer $user */
        $user = $this->getUser();

        if (!$user) {
            $responseCode = Response::HTTP_UNAUTHORIZED;
            throw new UnauthorizedHttpException('Bearer', 'User not found or not authenticated.');
        }

        $klassenId = $updateKlasseRequest->getKlassenId();

        if (!$klassenId) {
            $responseCode = Response::HTTP_BAD_REQUEST;
            throw new BadRequestHttpException('Missing "klassen_id" in request body.');
        }

        $klasse = $this->entityManager->getRepository(Klassen::class)->find($klassenId);

        if (!$klasse) {
            $responseCode = Response::HTTP_NOT_FOUND;
            throw new NotFoundHttpException(sprintf('Klasse with ID "%s" not found.', $klassenId));
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

        $persoenlicheDaten->setKlassen($klasse);
        $this->entityManager->flush();

        $responseCode = Response::HTTP_OK;
        // $responseHeaders können hier gesetzt werden, falls nötig

        // Rückgabe gemäß OpenAPI-Spezifikation (UpdateKlasse200Response DTO)
        $response = new UpdateKlasse200Response(); // Angenommen, dieses DTO existiert
        $response->setMessage('Klasse updated successfully.');
        $response->setKlassenId($klasse->getKlassenId());

        return $response;
    }
}
