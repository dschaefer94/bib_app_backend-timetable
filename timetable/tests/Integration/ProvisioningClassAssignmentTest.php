<?php

namespace App\Tests\Integration;

use App\Entity\Benutzer;
use App\Entity\CalendarSource;
use App\Service\KeycloakUserProvisioningService;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\RequiresPhp; // no-op import to keep style consistent
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Integration test to ensure that class information from JWT claims is
 * resolved to an existing CalendarSource and assigned to the provisioned user.
 */
class ProvisioningClassAssignmentTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::$kernel->getContainer();
        $this->entityManager = $container->get('doctrine')->getManager();

        try {
            $this->dropAndCreateSchema();
        } catch (\Throwable $e) {
            $this->markTestSkipped('Database not available: ' . $e->getMessage());
        }
    }

    private function dropAndCreateSchema(): void
    {
        $metadatas = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($this->entityManager);
        // Aggressive cleanup of DB objects that may exist due to migrations
        $conn = $this->entityManager->getConnection();
        try {
            $conn->executeStatement('DROP FUNCTION IF EXISTS import_calendar_for_class(INT, JSONB, BOOLEAN)');
        } catch (\Throwable $e) {
            // ignore
        }
        try {
            $conn->executeStatement('DROP TABLE IF EXISTS stundenplan_neu_klasse');
        } catch (\Throwable $e) {
            // ignore
        }
        try {
            $conn->executeStatement('DROP TABLE IF EXISTS klassen CASCADE');
        } catch (\Throwable $e) {
            // ignore
        }
        try {
            $conn->executeStatement('ALTER TABLE stundenplan_neu DROP COLUMN IF EXISTS fingerprint');
        } catch (\Throwable $e) {
            // ignore
        }

        $schemaTool->dropSchema($metadatas);
        $schemaTool->createSchema($metadatas);
    }

    public function testProvisioningAssignsExistingClassFromClaims(): void
    {
        // Create a CalendarSource that should be matched by the provisioning service
        $testClassName = 'TestklasseProvision';
        $calendar = new CalendarSource();
        $calendar->setClassName($testClassName);
        $calendar->setIcalLink('http://example.local/ical.ics');
        $this->entityManager->persist($calendar);
        $this->entityManager->flush();

        $provisioningService = self::getContainer()->get(KeycloakUserProvisioningService::class);

        $claims = [
            'sub' => 'prov-class-123',
            'email' => 'prov.class@example.com',
            'given_name' => 'Prov',
            'family_name' => 'Class',
            'attributes' => [
                'klasse' => [$testClassName]
            ]
        ];

        $benutzer = $provisioningService->provisionUserFromJwtClaims($claims);

        $this->assertNotNull($benutzer->getPersoenlicheDaten(), 'Personal data should be created for provisioned user');
        $this->assertNotNull($benutzer->getPersoenlicheDaten()->getKlasse(), 'Class should be assigned to personal data');
        $this->assertEquals($testClassName, $benutzer->getPersoenlicheDaten()->getKlasse()->getClassName());
    }
}

