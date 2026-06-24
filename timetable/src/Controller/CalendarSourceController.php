<?php

namespace App\Controller;

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
            // update last synced time
            $source->setLastSyncedAt(new \DateTime());
            $this->em->flush();
        } catch (\Throwable $e) {
            // Return created but with import error details
            return new JsonResponse(['id' => $source->getId(), 'import_error' => $e->getMessage()], Response::HTTP_CREATED);
        }

        return new JsonResponse(['id' => $source->getId(), 'import_summary' => $summary], Response::HTTP_CREATED);
    }
}

