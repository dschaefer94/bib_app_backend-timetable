<?php

namespace App\Tests\Component;

use App\Entity\Benutzer;
use App\Entity\PersoenlicheDaten;
use App\Entity\CalendarSource;
use App\Service\KeycloakUserProvisioningService;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Uuid;

class ProvisioningMissingClassTest extends KernelTestCase
{
    public function testProvisioningCreatesDummyClassWhenNoClassClaimProvided(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();

        $service = new KeycloakUserProvisioningService($em, new NullLogger());

        $sub = Uuid::v4()->toRfc4122();
        $claims = [
            'sub' => $sub,
            'email' => 'provision-test@example.com',
            'given_name' => 'Provision',
            'family_name' => 'Tester',
            // Intentionally no 'klasse' or attributes
        ];

        $benutzer = $service->provisionUserFromJwtClaims($claims);

        $this->assertInstanceOf(Benutzer::class, $benutzer);

        $pd = $benutzer->getPersoenlicheDaten();
        $this->assertInstanceOf(PersoenlicheDaten::class, $pd);

        $klasse = $pd->getKlasse();
        $this->assertInstanceOf(CalendarSource::class, $klasse);
        $this->assertSame('Dummyklasse', $klasse->getClassName());
    }
}

