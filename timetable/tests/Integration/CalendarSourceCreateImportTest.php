<?php

namespace App\Tests\Integration;

use App\Controller\CalendarSourceController;
use App\Entity\Benutzer;
use App\Entity\CalendarSource;
use App\Service\CalendarImportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Security\Core\User\UserInterface;

class CalendarSourceCreateImportTest extends DatabaseIntegrationTest
{
    public function testCreateClassImportsEventsAndAssignsCurrentUserToNewClass(): void
    {
        $dummyUser = $this->entityManager->getRepository(Benutzer::class)->findOneBy(['email' => 'dummyuser@example.com']);
        $this->assertInstanceOf(Benutzer::class, $dummyUser);

        $ical = "BEGIN:VCALENDAR\nVERSION:2.0\nBEGIN:VEVENT\nUID:123e4567-e89b-12d3-a456-426614174000\nSUMMARY:Imported Event\nDTSTART:20260630T090000Z\nDTEND:20260630T100000Z\nLOCATION:Room 42\nEND:VEVENT\nEND:VCALENDAR";
        $importService = new CalendarImportService(
            $this->entityManager,
            new MockHttpClient(new MockResponse($ical, ['http_version' => '1.1']))
        );

        $controller = new class($this->entityManager, $importService, $dummyUser) extends CalendarSourceController {
            public function __construct(EntityManagerInterface $em, CalendarImportService $importService, private Benutzer $user)
            {
                parent::__construct($em, $importService);
            }

            public function getUser(): ?UserInterface
            {
                return $this->user;
            }
        };

        $className = 'CREATE_IMPORT_TEST';
        $request = new Request([], [], [], [], [], [], json_encode([
            'name' => $className,
            'ical_link' => 'https://example.test/feed.ics',
        ]));

        $response = $controller->create($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(201, $response->getStatusCode());

        $payload = json_decode($response->getContent(), true);
        $this->assertIsArray($payload);
        $this->assertArrayHasKey('import_summary', $payload);
        $this->assertSame(1, $payload['import_summary']['inserted']);

        $createdSource = $this->entityManager->getRepository(CalendarSource::class)->findOneBy(['className' => $className]);
        $this->assertInstanceOf(CalendarSource::class, $createdSource);

        $this->entityManager->refresh($dummyUser);
        $this->assertNotNull($dummyUser->getPersoenlicheDaten());
        $this->assertSame($className, $dummyUser->getPersoenlicheDaten()->getKlasse()?->getClassName());

        $count = (int) $this->entityManager->getConnection()->fetchOne(
            'SELECT count(*) FROM stundenplan_neu WHERE klasse = ?',
            [$className]
        );
        $this->assertSame(1, $count);
    }
}
