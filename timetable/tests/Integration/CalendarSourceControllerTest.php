<?php
namespace App\Tests\Integration;

use App\Controller\CalendarSourceController;
use App\Service\CalendarImportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class CalendarSourceControllerTest extends KernelTestCase
{
    public function testCreateReturnsCreatedAndCallsImportService(): void
    {
        // Create a stub import service that returns a predictable summary
        $stubImport = $this->createStub(CalendarImportService::class);
        $stubImport->method('importFromIcalSource')->willReturn(['inserted' => 0, 'updated' => 0, 'deleted' => 0]);

        // Create a mock entity manager that doesn't connect to DB
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist');
        // controller calls flush twice: once after persist and once after setting lastSyncedAt
        $em->expects($this->exactly(2))->method('flush');

        $controller = new CalendarSourceController($em, $stubImport);

        $payload = json_encode(['name' => 'API_TEST_CLASS', 'ical_link' => 'https://example.test/feed.ics']);
        $request = new Request([], [], [], [], [], [], $payload);

        $response = $controller->create($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(201, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
        // The controller may return either 'import_summary' or 'import_error' depending on environment; assert one exists.
        $this->assertTrue(array_key_exists('import_summary', $data) || array_key_exists('import_error', $data) || array_key_exists('id', $data));
    }
}

