<?php

namespace App\Controller;

use App\Entity\Benutzer;
use App\Entity\CalendarSource;
use App\Service\CalendarImportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/klassen')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class CalendarSourceController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em, private CalendarImportService $importService) {}

    #[Route('', name: 'create_calendar_source', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $name = trim($data['name'] ?? '');
        $ical = trim($data['ical_link'] ?? '');

        if ($name === '') {
            return new JsonResponse(['type' => '/problems/invalid-request', 'title' => 'Missing name', 'status' => 400, 'detail' => 'Field "name" is required.'], Response::HTTP_BAD_REQUEST);
        }

        if ($ical !== '' && filter_var($ical, FILTER_VALIDATE_URL) === false) {
            return new JsonResponse(['type' => '/problems/invalid-request', 'title' => 'Invalid ical_link', 'status' => 400, 'detail' => 'Field "ical_link" must be a valid URL if present.'], Response::HTTP_BAD_REQUEST);
        }

        $source = new CalendarSource();
        $source->setClassName($name);
        if ($ical !== '') {
            $source->setIcalLink($ical);
        }

        $this->em->persist($source);
        $this->em->flush();

        // Initial import: match-first (avoid marking all events as new)
        try {
            $summary = $this->importService->importFromIcalSource($source, false);
            $user = $this->getUser();
            if ($user instanceof Benutzer && $user->getPersoenlicheDaten()) {
                $persoenlicheDaten = $user->getPersoenlicheDaten();
                if ($persoenlicheDaten->getKlasse()?->getId() !== $source->getId()) {
                    $persoenlicheDaten->setKlasse($source);
                    $this->em->flush();
                }
            }
            // update last synced time
            $source->setLastSyncedAt(new \DateTime());
            $this->em->flush();
        } catch (\Throwable $e) {
            // Return created but with import error details
            return new JsonResponse(['id' => $source->getId(), 'import_error' => $e->getMessage()], Response::HTTP_CREATED);
        }

        return new JsonResponse(['id' => $source->getId(), 'import_summary' => $summary], Response::HTTP_CREATED);
    }

    #[Route('/{id}/sync', name: 'sync_calendar_source', methods: ['POST'])]
    public function sync(int $id): JsonResponse
    {
        $source = $this->em->getRepository(CalendarSource::class)->find($id);
        if (!$source) {
            return new JsonResponse([
                'type' => '/problems/not-found',
                'title' => 'Klasse not found',
                'status' => 404,
                'detail' => sprintf('No class with id "%d" found.', $id),
            ], Response::HTTP_NOT_FOUND);
        }

        if (!$source->getIcalLink()) {
            return new JsonResponse([
                'type' => '/problems/invalid-request',
                'title' => 'No ical_link configured',
                'status' => 400,
                'detail' => 'Set an ical_link before running sync.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $summary = $this->importService->importFromIcalSource($source, true);
        $source->setLastSyncedAt(new \DateTime());
        $this->em->flush();

        return new JsonResponse([
            'id' => $source->getId(),
            'class' => $source->getClassName(),
            'import_summary' => $summary,
        ], Response::HTTP_OK);
    }
}
