<?php

namespace App\Tests\Integration;

use App\Entity\Benutzer;
use App\Entity\PersoenlicheDaten;
use App\Entity\CalendarSource;
use App\Service\KeycloakUserProvisioningService;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Uuid;

class CalendarProvisioningIntegrationTest extends KernelTestCase
{
    public function testProvisioningCreatesCalendarAndAssignsToPersonalData(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();

        $service = new KeycloakUserProvisioningService($em, new NullLogger());

        $sub = Uuid::v4()->toRfc4122();
        $claims = [
            'sub' => $sub,
            'email' => 'integration-test@example.com',
            'given_name' => 'Integration',
            'family_name' => 'Tester',
            // No class provided
        ];

        $benutzer = $service->provisionUserFromJwtClaims($claims);

        // Reload from DB to ensure flush persisted
        $em->refresh($benutzer);

        $this->assertInstanceOf(Benutzer::class, $benutzer);
        $pd = $benutzer->getPersoenlicheDaten();
        $this->assertNotNull($pd);

        $klasse = $pd->getKlasse();
        $this->assertNotNull($klasse);
        $this->assertSame('Dummyklasse', $klasse->getClassName());

        // CalendarSource should exist in repository
        $calRepo = $em->getRepository(CalendarSource::class);
        $found = $calRepo->findOneBy(['className' => 'Dummyklasse']);
        $this->assertNotNull($found);
    }
}

