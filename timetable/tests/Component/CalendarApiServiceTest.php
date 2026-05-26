<?php

namespace App\Tests\Component;

use App\Api\CalendarApiService;
use App\Entity\Benutzer;
use App\Entity\CalendarSource;
use App\Entity\PersoenlicheDaten;
use App\Entity\StundenplanNeu;
use App\OpenApi\Model\CalendarEvent;
use App\OpenApi\Model\GetCalendar200Response;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations; // Hinzugefügt

#[AllowMockObjectsWithoutExpectations] // Hinzugefügt
class CalendarApiServiceTest extends TestCase
{
    private $entityManagerMock;
    private $persoenlicheDatenRepositoryMock;
    private $stundenplanNeuRepositoryMock;
    private CalendarApiService $calendarApiService;

    protected function setUp(): void
    {
        $this->entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $this->persoenlicheDatenRepositoryMock = $this->createMock(EntityRepository::class);
        $this->stundenplanNeuRepositoryMock = $this->createMock(EntityRepository::class);

        $this->entityManagerMock->method('getRepository')
            ->willReturnMap([
                [PersoenlicheDaten::class, $this->persoenlicheDatenRepositoryMock],
                [StundenplanNeu::class, $this->stundenplanNeuRepositoryMock],
            ]);

        $this->calendarApiService = new CalendarApiService($this->entityManagerMock);
    }

    public function testCalendarApiReturnsEventsForExistingUserAndClass(): void
    {
        $dummyUserUuid = Uuid::fromString('550e8400-e29b-41d4-a716-446655440000');
        $klasseName = 'Dummyklasse';

        // Mock-Entitäten erstellen
        $mockBenutzer = (new Benutzer())->setEmail('dummy@example.com')->setPassword('hash')->setIsAdmin(true);
        // Setze die ID manuell, da sie nicht von Doctrine generiert wird
        $reflection = new \ReflectionClass($mockBenutzer);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($mockBenutzer, $dummyUserUuid);


        $mockCalendarSource = (new CalendarSource())->setClassName($klasseName)->setIcalLink('http://example.com/ical');
        $mockPersoenlicheDaten = (new PersoenlicheDaten())
            ->setBenutzer($mockBenutzer)
            ->setName('Mustermann')
            ->setVorname('Max')
            ->setKlasse($mockCalendarSource);

        $mockStundenplanNeuEntry = (new StundenplanNeu())
            // ->setId(Uuid::v4()) // Entfernt
            ->setSummary('Test Event')
            ->setDescription('Test Description')
            ->setStart(new \DateTimeImmutable('2026-01-01 09:00:00'))
            ->setEnd(new \DateTimeImmutable('2026-01-01 10:00:00'))
            ->setLocation('Test Location')
            ->setLabel('neu')
            ->setKategorie('unterricht')
            ->setKlasse($klasseName);

        // Setze die ID für StundenplanNeu mit Reflection
        $reflectionStundenplanNeu = new \ReflectionClass($mockStundenplanNeuEntry);
        $propertyStundenplanNeu = $reflectionStundenplanNeu->getProperty('id');
        $propertyStundenplanNeu->setAccessible(true);
        $propertyStundenplanNeu->setValue($mockStundenplanNeuEntry, Uuid::v4());


        // Repositories konfigurieren
        $this->persoenlicheDatenRepositoryMock->expects($this->once())
            ->method('findOneBy')
            ->with(['benutzer' => $dummyUserUuid])
            ->willReturn($mockPersoenlicheDaten);

        $this->stundenplanNeuRepositoryMock->expects($this->once())
            ->method('findBy')
            ->with(['klasse' => $klasseName])
            ->willReturn([$mockStundenplanNeuEntry]);

        $responseCode = 0;
        $responseHeaders = [];
        $response = $this->calendarApiService->getCalendar($responseCode, $responseHeaders);

        $this->assertEquals(200, $responseCode);
        $this->assertInstanceOf(GetCalendar200Response::class, $response);
        $this->assertTrue($response->isSuccess()); // Geändert von getSuccess() zu isSuccess()
        $this->assertCount(1, $response->getData());
        $this->assertInstanceOf(CalendarEvent::class, $response->getData()[0]);
        $this->assertEquals('Test Event', $response->getData()[0]->getSummary());
    }

    public function testCalendarApiReturns404IfPersonalDataNotFound(): void
    {
        $dummyUserUuid = Uuid::fromString('550e8400-e29b-41d4-a716-446655440000');

        $this->persoenlicheDatenRepositoryMock->expects($this->once())
            ->method('findOneBy')
            ->with(['benutzer' => $dummyUserUuid])
            ->willReturn(null);

        $responseCode = 0;
        $responseHeaders = [];
        $response = $this->calendarApiService->getCalendar($responseCode, $responseHeaders);

        $this->assertEquals(404, $responseCode);
        $this->assertInstanceOf(\App\OpenApi\Model\Problem::class, $response);
        $this->assertEquals('Calendar not found', $response->getTitle());
    }

    public function testCalendarApiReturns404IfClassDataNotFound(): void
    {
        $dummyUserUuid = Uuid::fromString('550e8400-e29b-41d4-a716-446655440000');

        // Mock PersoenlicheDaten ohne Klasse
        $mockBenutzer = (new Benutzer())->setEmail('dummy@example.com')->setPassword('hash')->setIsAdmin(true);
        $reflection = new \ReflectionClass($mockBenutzer);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($mockBenutzer, $dummyUserUuid);

        $mockPersoenlicheDaten = (new PersoenlicheDaten())
            ->setBenutzer($mockBenutzer)
            ->setName('Mustermann')
            ->setVorname('Max')
            ->setKlasse(null); // Klasse ist null

        $this->persoenlicheDatenRepositoryMock->expects($this->once())
            ->method('findOneBy')
            ->with(['benutzer' => $dummyUserUuid])
            ->willReturn($mockPersoenlicheDaten);

        $responseCode = 0;
        $responseHeaders = [];
        $response = $this->calendarApiService->getCalendar($responseCode, $responseHeaders);

        $this->assertEquals(404, $responseCode);
        $this->assertInstanceOf(\App\OpenApi\Model\Problem::class, $response);
        $this->assertEquals('Calendar not found', $response->getTitle());
    }

    public function testCalendarApiReturnsEmptyDataIfNoEntriesFound(): void
    {
        $dummyUserUuid = Uuid::fromString('550e8400-e29b-41d4-a716-446655440000');
        $klasseName = 'Dummyklasse';

        // Mock-Entitäten erstellen
        $mockBenutzer = (new Benutzer())->setEmail('dummy@example.com')->setPassword('hash')->setIsAdmin(true);
        $reflection = new \ReflectionClass($mockBenutzer);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($mockBenutzer, $dummyUserUuid);

        $mockCalendarSource = (new CalendarSource())->setClassName($klasseName)->setIcalLink('http://example.com/ical');
        $mockPersoenlicheDaten = (new PersoenlicheDaten())
            ->setBenutzer($mockBenutzer)
            ->setName('Mustermann')
            ->setVorname('Max')
            ->setKlasse($mockCalendarSource);

        // Setze die ID für StundenplanNeu mit Reflection
        $mockStundenplanNeuEntry = (new StundenplanNeu())
            ->setSummary('Test Event')
            ->setDescription('Test Description')
            ->setStart(new \DateTimeImmutable('2026-01-01 09:00:00'))
            ->setEnd(new \DateTimeImmutable('2026-01-01 10:00:00'))
            ->setLocation('Test Location')
            ->setLabel('neu')
            ->setKategorie('unterricht')
            ->setKlasse($klasseName);

        $reflectionStundenplanNeu = new \ReflectionClass($mockStundenplanNeuEntry);
        $propertyStundenplanNeu = $reflectionStundenplanNeu->getProperty('id');
        $propertyStundenplanNeu->setAccessible(true);
        $propertyStundenplanNeu->setValue($mockStundenplanNeuEntry, Uuid::v4());

        // Repositories konfigurieren
        $this->persoenlicheDatenRepositoryMock->expects($this->once())
            ->method('findOneBy')
            ->with(['benutzer' => $dummyUserUuid])
            ->willReturn($mockPersoenlicheDaten);

        $this->stundenplanNeuRepositoryMock->expects($this->once())
            ->method('findBy')
            ->with(['klasse' => $klasseName])
            ->willReturn([]); // Keine Stundenplan-Einträge gefunden

        $responseCode = 0;
        $responseHeaders = [];
        $response = $this->calendarApiService->getCalendar($responseCode, $responseHeaders);

        $this->assertEquals(200, $responseCode);
        $this->assertInstanceOf(GetCalendar200Response::class, $response);
        $this->assertTrue($response->isSuccess()); // Geändert von getSuccess() zu isSuccess()
        $this->assertCount(0, $response->getData()); // Erwarte leeres Array
    }
}
