<?php

namespace App\Tests\Integration;

use App\Entity\Benutzer;
use App\Entity\CalendarSource;
use App\Entity\PersoenlicheDaten;
use App\Entity\StundenplanNeu;
use App\DataFixtures\AppFixtures;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Uuid;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\ORM\Tools\SchemaTool; // Added this line

class DatabaseIntegrationTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::$kernel->getContainer();
        $this->entityManager = $container->get('doctrine')->getManager();

        // Ensure the database schema is up-to-date for tests
        $this->dropAndCreateSchema();

        // Sicherstellen, dass die Datenbank für jeden Test sauber ist und Fixtures geladen werden
        $this->loadFixtures();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Schließt den EntityManager, um Memory Leaks zu vermeiden
        if ($this->entityManager) {
            $this->entityManager->close();
            $this->entityManager = null;
        }
    }

    private function dropAndCreateSchema(): void
    {
        $metadatas = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($this->entityManager);
        $schemaTool->dropSchema($metadatas);
        $schemaTool->createSchema($metadatas);
    }

    private function loadFixtures(): void
    {
        // Purge und lade Fixtures für einen sauberen Testzustand
        $purger = new ORMPurger($this->entityManager);
        $executor = new ORMExecutor($this->entityManager, $purger);
        // Übergebe den passwordHasher an die AppFixtures
        $executor->execute([new AppFixtures()], true);
    }

    public function testDummyUserAndPersonalDataAreLoaded(): void
    {
        // Find the dummy user by email, as the UUID is dynamically generated
        /** @var Benutzer|null $user */
        $user = $this->entityManager->getRepository(Benutzer::class)->findOneBy(['email' => 'dummyuser@example.com']);
        $this->assertNotNull($user, 'Dummy user should be found.');
        $this->assertEquals('dummyuser@example.com', $user->getEmail());

        /** @var PersoenlicheDaten|null $persoenlicheDaten */
        $persoenlicheDaten = $this->entityManager->getRepository(PersoenlicheDaten::class)->findOneBy(['benutzer' => $user]);
        $this->assertNotNull($persoenlicheDaten, 'Personal data for dummy user should be found.');
        $this->assertEquals('Max', $persoenlicheDaten->getVorname());
        $this->assertEquals('Mustermann', $persoenlicheDaten->getName());

        /** @var CalendarSource|null $calendarSource */
        $calendarSource = $persoenlicheDaten->getKlasse();
        $this->assertNotNull($calendarSource, 'Calendar source should be associated with personal data.');
        $this->assertEquals('Dummyklasse', $calendarSource->getClassName());
    }

    public function testStundenplanNeuEntriesAreLoaded(): void
    {
        $klasseName = 'Dummyklasse';

        /** @var CalendarSource|null $calendarSource */
        $calendarSource = $this->entityManager->getRepository(CalendarSource::class)->findOneBy(['className' => $klasseName]);
        $this->assertNotNull($calendarSource, 'Calendar source for Dummyklasse should be found.');

        /** @var StundenplanNeu[] $entries */
        $entries = $this->entityManager->getRepository(StundenplanNeu::class)->findBy(['klasse' => $klasseName]);
        $this->assertGreaterThan(0, count($entries), 'At least one StundenplanNeu entry should be found.');

        foreach ($entries as $entry) {
            $this->assertInstanceOf(StundenplanNeu::class, $entry);
            $this->assertNotNull($entry->getId(), 'StundenplanNeu entry should have an ID.');
            $this->assertNotEmpty($entry->getSummary(), 'StundenplanNeu entry should have a summary.');
            $this->assertEquals($klasseName, $entry->getKlasse(), 'StundenplanNeu entry should belong to Dummyklasse.');
            $this->assertInstanceOf(\DateTimeImmutable::class, $entry->getStart(), 'StundenplanNeu entry start date should be DateTimeImmutable.');
            $this->assertInstanceOf(\DateTimeImmutable::class, $entry->getEnd(), 'StundenplanNeu entry end date should be DateTimeImmutable.');
        }
    }
}
