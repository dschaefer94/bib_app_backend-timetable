<?php

namespace App\Tests\Component;

use App\Entity\CalendarSource;
use App\Service\CalendarImportService;
use App\Tests\Integration\DatabaseIntegrationTest;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class CalendarImportServiceTest extends DatabaseIntegrationTest
{
    public function testImportCreatesUpdatesAndDeletesEventsWithStringLabels(): void
    {
        $initialIcal = "BEGIN:VCALENDAR\nVERSION:2.0\nBEGIN:VEVENT\nUID:uid1\nSUMMARY:Test Event\nDESCRIPTION:Original description\nDTSTART:20260623T090000Z\nDTEND:20260623T100000Z\nLOCATION:Room 1\nEND:VEVENT\nBEGIN:VEVENT\nUID:uid2\nSUMMARY:Deleted Event\nDESCRIPTION:Will be removed\nDTSTART:20260623T110000Z\nDTEND:20260623T120000Z\nLOCATION:Room 2\nEND:VEVENT\nEND:VCALENDAR";
        $updatedIcal = "BEGIN:VCALENDAR\nVERSION:2.0\nBEGIN:VEVENT\nUID:uid1\nSUMMARY:Test Event\nDESCRIPTION:Updated description\nDTSTART:20260623T090000Z\nDTEND:20260623T100000Z\nLOCATION:Room 1\nEND:VEVENT\nBEGIN:VEVENT\nUID:uid3\nSUMMARY:New Event\nDESCRIPTION:Brand new\nDTSTART:20260624T090000Z\nDTEND:20260624T100000Z\nLOCATION:Room 3\nEND:VEVENT\nEND:VCALENDAR";

        $source = new CalendarSource();
        $source->setClassName('TEST_IMPORT');
        $source->setIcalLink('https://example.test/feed.ics');
        $this->entityManager->persist($source);
        $this->entityManager->flush();

        $importService = new CalendarImportService(
            $this->entityManager,
            new MockHttpClient(new MockResponse($initialIcal, ['http_version' => '1.1']))
        );

        $firstResult = $importService->importFromIcalSource($source, true);
        $this->assertSame(2, $firstResult['inserted']);
        $this->assertSame(0, $firstResult['updated']);
        $this->assertSame(0, $firstResult['deleted']);

        $importService = new CalendarImportService(
            $this->entityManager,
            new MockHttpClient(new MockResponse($updatedIcal, ['http_version' => '1.1']))
        );

        $secondResult = $importService->importFromIcalSource($source, true);

        $this->assertSame(1, $secondResult['inserted']);
        $this->assertSame(1, $secondResult['updated']);
        $this->assertSame(1, $secondResult['deleted']);

        $rows = $this->entityManager->getConnection()->fetchAllAssociative(
            'SELECT change_type, summary FROM geaenderte_termine ORDER BY summary'
        );

        $this->assertCount(2, $rows);
        $this->assertContains(['change_type' => 'geändert', 'summary' => 'Test Event'], $rows);
        $this->assertContains(['change_type' => 'gelöscht', 'summary' => 'Deleted Event'], $rows);
    }
}
