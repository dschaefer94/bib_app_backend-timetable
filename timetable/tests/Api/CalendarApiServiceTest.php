<?php

namespace App\Tests\Api;

use App\DataFixtures\AppFixtures;
use App\Api\CalendarApiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseToolInterface;

class CalendarApiServiceTest extends KernelTestCase
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var CalendarApiService */
    private $calendarApiService;

    /** @var DatabaseToolInterface */
    protected DatabaseToolInterface $databaseTool;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->databaseTool = static::getContainer()->get(DatabaseToolCollection::class)->get();

        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->calendarApiService = static::getContainer()->get(CalendarApiService::class);

        // Lade die Fixtures, um eine saubere Datenbank für jeden Test zu gewährleisten
        $this->databaseTool->loadFixtures([
            AppFixtures::class,
        ]);
    }

    public function testCalendarApiReturnsEventsForDummyUser(): void
    {
        $responseCode = 0;
        $responseHeaders = [];

        $response = $this->calendarApiService->getCalendar($responseCode, $responseHeaders);

        // Überprüfe, ob der Statuscode 200 ist
        $this->assertEquals(200, $responseCode, 'Der API-Aufruf sollte erfolgreich sein (Status 200).');

        // Überprüfe, ob die Antwort ein GetCalendar200Response-Objekt ist
        $this->assertInstanceOf(\App\OpenApi\Model\GetCalendar200Response::class, $response, 'Die Antwort sollte ein GetCalendar200Response-Objekt sein.');

        // Überprüfe, ob 'success' true ist
        $this->assertTrue($response->getSuccess(), 'Das "success"-Feld in der Antwort sollte true sein.');

        // Überprüfe, ob Daten vorhanden sind (mindestens die aus den Fixtures)
        $this->assertNotEmpty($response->getData(), 'Das "data"-Feld in der Antwort sollte nicht leer sein.');

        // Optional: Überprüfe die Anzahl der Events oder spezifische Event-Details
        // $this->assertCount(anzahl_der_erwarteten_events, $response->getData());
        // $this->assertEquals('Termin: Klausur', $response->getData()[0]->getSummary());
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Schließe den EntityManager, um Speicherlecks zu vermeiden
        $this->entityManager->close();
        $this->entityManager = null;
    }
}
