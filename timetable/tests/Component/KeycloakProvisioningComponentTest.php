<?php

namespace App\Tests\Component;

use App\Entity\CalendarSource;
use App\Service\KeycloakUserProvisioningService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityRepository;

/**
 * Component test for KeycloakUserProvisioningService focusing on class extraction
 * behaviour when the claim contains a templated value like "${klasse]".
 */
class KeycloakProvisioningComponentTest extends TestCase
{
    public function testProvisioningCreatesFallbackDummyklasseWhenClaimMalformed(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        // Calendar repository should return null for both the provided malformed class
        // and for the Dummyklasse lookup so that service will create a new Dummyklasse.
        $calendarRepo = $this->createMock(EntityRepository::class);
        $calendarRepo->method('findOneBy')->willReturn(null);

        // Provide a generic repository mock for other entity types (Benutzer, PersoenlicheDaten)
        $genericRepo = $this->createMock(EntityRepository::class);

        $entityManager->method('getRepository')
            ->willReturnCallback(function ($class) use ($calendarRepo, $genericRepo) {
                if ($class === CalendarSource::class) {
                    return $calendarRepo;
                }
                return $genericRepo;
            });

        // Capture persist calls to assert that a CalendarSource with className 'Dummyklasse' is persisted
        $persisted = [];
        $entityManager->expects($this->atLeastOnce())
            ->method('persist')
            ->willReturnCallback(function ($obj) use (&$persisted) {
                $persisted[] = $obj;
            });

        $entityManager->expects($this->once())->method('flush');

        $service = new KeycloakUserProvisioningService($entityManager, $logger);

        $claims = [
            'sub' => 'malformed-claim-1',
            'email' => 'malformed@example.com',
            'given_name' => 'Bad',
            'family_name' => 'Template',
            'attributes' => [
                // intentionally using the template-like value the user mentioned
                'klasse' => ['${klasse]']
            ]
        ];

        $benutzer = $service->provisionUserFromJwtClaims($claims);

        // Ensure persist captured a CalendarSource instance (the Dummyklasse fallback)
        $foundDummy = false;
        foreach ($persisted as $p) {
            if ($p instanceof CalendarSource && $p->getClassName() === 'Dummyklasse') {
                $foundDummy = true;
                break;
            }
        }

        $this->assertTrue($foundDummy, 'Service should persist a fallback CalendarSource named Dummyklasse when claim is malformed');
        $this->assertNotNull($benutzer->getPersoenlicheDaten());
        $this->assertEquals('Dummyklasse', $benutzer->getPersoenlicheDaten()->getKlasse()->getClassName());
    }
}

