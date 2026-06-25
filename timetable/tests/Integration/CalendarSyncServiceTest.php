<?php

namespace App\Tests\Integration;

use App\Entity\CalendarSource;
use App\Service\CalendarSyncService;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class CalendarSyncServiceTest extends DatabaseIntegrationTest
{
    public function testInitialSyncDoesNotCreateChangeEntriesButLaterSyncDoes(): void
    {
        $source = new CalendarSource();
        $source->setClassName('SYNC_BASELINE_TEST');
        $source->setIcalLink('https://example.test/feed.ics');
        $this->entityManager->persist($source);
        $this->entityManager->flush();

        $initialFeed = "BEGIN:VCALENDAR\nVERSION:2.0\nBEGIN:VEVENT\nUID:123e4567-e89b-12d3-a456-426614174000\nSUMMARY:Initial Event\nDTSTART:20260630T090000Z\nDTEND:20260630T100000Z\nLOCATION:Room 1\nEND:VEVENT\nBEGIN:VEVENT\nUID:123e4567-e89b-12d3-a456-426614174001\nSUMMARY:Second Event\nDTSTART:20260630T110000Z\nDTEND:20260630T120000Z\nLOCATION:Room 2\nEND:VEVENT\nEND:VCALENDAR";
        $updatedFeed = "BEGIN:VCALENDAR\nVERSION:2.0\nBEGIN:VEVENT\nUID:123e4567-e89b-12d3-a456-426614174000\nSUMMARY:Initial Event Updated\nDTSTART:20260630T090000Z\nDTEND:20260630T100000Z\nLOCATION:Room 1\nEND:VEVENT\nBEGIN:VEVENT\nUID:123e4567-e89b-12d3-a456-426614174002\nSUMMARY:New Event\nDTSTART:20260630T130000Z\nDTEND:20260630T140000Z\nLOCATION:Room 3\nEND:VEVENT\nEND:VCALENDAR";

        $service = new CalendarSyncService(
            $this->entityManager,
            new MockHttpClient(new MockResponse($initialFeed, ['http_version' => '1.1']))
        );
        $service->syncSource($source);

        $countAfterInitial = (int) $this->entityManager->getConnection()->fetchOne(
            'SELECT count(*) FROM geaenderte_termine WHERE klasse = ?',
            ['SYNC_BASELINE_TEST']
        );
        $this->assertSame(0, $countAfterInitial);

        $service = new CalendarSyncService(
            $this->entityManager,
            new MockHttpClient(new MockResponse($updatedFeed, ['http_version' => '1.1']))
        );
        $service->syncSource($source);

        $rows = $this->entityManager->getConnection()->fetchAllAssociative(
            'SELECT change_type, summary FROM geaenderte_termine WHERE klasse = ? ORDER BY summary',
            ['SYNC_BASELINE_TEST']
        );

        $this->assertCount(3, $rows);
        $this->assertContains(['change_type' => 'geändert', 'summary' => 'Initial Event Updated'], $rows);
        $this->assertContains(['change_type' => 'neu', 'summary' => 'New Event'], $rows);
        $this->assertContains(['change_type' => 'gelöscht', 'summary' => 'Second Event'], $rows);
    }
}
