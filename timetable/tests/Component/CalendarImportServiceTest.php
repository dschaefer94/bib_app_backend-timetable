<?php
namespace App\Tests\Component;

use App\Entity\CalendarSource;
use App\Service\CalendarImportService;
use App\Tests\Integration\DatabaseIntegrationTest;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class CalendarImportServiceTest extends DatabaseIntegrationTest
{
    public function testImportCreatesEventAndMapping(): void
    {
        // Prepare a simple iCal feed with one event
        $ical = "BEGIN:VCALENDAR\nVERSION:2.0\nBEGIN:VEVENT\nUID:uid1\nSUMMARY:Test Event\nDTSTART:20260623T090000Z\nDTEND:20260623T100000Z\nLOCATION:Room 1\nEND:VEVENT\nEND:VCALENDAR";

        $mockResponse = new MockResponse($ical, ['http_version' => '1.1']);
        $mockClient = new MockHttpClient($mockResponse);

        // Create a CalendarSource in DB
        $source = new CalendarSource();
        $source->setClassName('TEST_IMPORT');
        $source->setIcalLink('https://example.test/feed.ics');
        $this->entityManager->persist($source);
        $this->entityManager->flush();

        // Instantiate service with mock client
        $importService = new CalendarImportService($this->entityManager, $mockClient);

        $result = $importService->importFromIcalSource($source, false);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('inserted', $result);
        $this->assertGreaterThanOrEqual(1, $result['inserted'], 'At least one event should have been inserted');

        // Verify join table mapping exists
        $conn = $this->entityManager->getConnection();
        $count = (int)$conn->fetchOne('SELECT count(*) FROM stundenplan_neu_klasse WHERE klassen_id = ?', [$source->getId()]);
        $this->assertGreaterThanOrEqual(1, $count, 'Join table should contain mapping for the new class');
    }
}

