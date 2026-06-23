<?php

namespace App\Tests\Integration;

use App\Entity\Benutzer;
use App\DataFixtures\AppFixtures;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;

/**
 * Integration tests for Keycloak JWT authentication.
 * Tests the full flow: JWT validation → User provisioning → Protected endpoint access.
 */
class KeycloakAuthTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::$kernel->getContainer();
        $this->entityManager = $container->get('doctrine')->getManager();

        $this->dropAndCreateSchema();
        $this->loadFixtures();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if ($this->entityManager) {
            $this->entityManager->close();
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
        $purger = new ORMPurger($this->entityManager);
        $executor = new ORMExecutor($this->entityManager, $purger);
        $executor->execute([new AppFixtures()], true);
    }

    /**
     * Test: User can be loaded after provisioning from JWT claims.
     */
    public function testUserProvisioningFromJwtClaims(): void
    {
        $provisioningService = self::getContainer()->get(\App\Service\KeycloakUserProvisioningService::class);

        // Simulate JWT claims from Keycloak
        $jwtClaims = [
            'sub' => 'user-123-keycloak-id',
            'email' => 'john.doe@example.com',
            'given_name' => 'John',
            'family_name' => 'Doe',
            'realm_access' => [
                'roles' => ['user', 'student']
            ]
        ];

        // Provision user from JWT claims
        $benutzer = $provisioningService->provisionUserFromJwtClaims($jwtClaims);

        // Verify user was created
        $this->assertNotNull($benutzer);
        $this->assertEquals('user-123-keycloak-id', $benutzer->getIdentityId());
        $this->assertEquals('john.doe@example.com', $benutzer->getEmail());

        // Verify personal data was created
        $persoenlicheDaten = $benutzer->getPersoenlicheDaten();
        $this->assertNotNull($persoenlicheDaten);
        $this->assertEquals('John', $persoenlicheDaten->getVorname());
        $this->assertEquals('Doe', $persoenlicheDaten->getName());

        // Verify roles were extracted
        $roles = $benutzer->getRoles();
        $this->assertContains('ROLE_USER', $roles);
        $this->assertContains('ROLE_STUDENT', $roles);
    }

    /**
     * Test: User provisioning updates existing user data.
     */
    public function testUserProvisioningUpdatesExistingUser(): void
    {
        $provisioningService = self::getContainer()->get(\App\Service\KeycloakUserProvisioningService::class);

        // Find dummy user from fixtures
        $dummyUser = $this->entityManager->getRepository(Benutzer::class)
            ->findOneBy(['email' => 'dummyuser@example.com']);

        $this->assertNotNull($dummyUser);
        $originalIdentityId = $dummyUser->getIdentityId();

        // Simulate update JWT claims with same identityId but different email
        $jwtClaims = [
            'sub' => $originalIdentityId,
            'email' => 'updated.email@example.com', // Different email
            'given_name' => 'Updated',
            'family_name' => 'Name'
        ];

        // Provision (should update)
        $updatedBenutzer = $provisioningService->provisionUserFromJwtClaims($jwtClaims);

        // Verify user was updated
        $this->assertEquals($originalIdentityId, $updatedBenutzer->getIdentityId());
        $this->assertEquals('updated.email@example.com', $updatedBenutzer->getEmail());
    }

    /**
     * Test: User extraction from Cognito JWT claims (AWS format).
     */
    public function testUserProvisioningFromCognitoJwtClaims(): void
    {
        $provisioningService = self::getContainer()->get(\App\Service\KeycloakUserProvisioningService::class);

        // Simulate JWT claims from AWS Cognito
        $jwtClaims = [
            'sub' => 'user-456-cognito-id',
            'email' => 'jane.smith@example.com',
            'given_name' => 'Jane',
            'family_name' => 'Smith',
            'cognito:groups' => ['admin', 'teachers']
        ];

        // Provision user from JWT claims
        $benutzer = $provisioningService->provisionUserFromJwtClaims($jwtClaims);

        // Verify user was created
        $this->assertNotNull($benutzer);
        $this->assertEquals('user-456-cognito-id', $benutzer->getIdentityId());
        $this->assertEquals('jane.smith@example.com', $benutzer->getEmail());

        // Verify Cognito groups are mapped to roles
        $roles = $benutzer->getRoles();
        $this->assertContains('ROLE_ADMIN', $roles);
        $this->assertContains('ROLE_TEACHERS', $roles);
    }

    /**
     * Test: Missing sub claim throws exception.
     */
    public function testUserProvisioningMissingSub(): void
    {
        $provisioningService = self::getContainer()->get(\App\Service\KeycloakUserProvisioningService::class);

        $jwtClaims = [
            'email' => 'no-sub@example.com',
            // Missing 'sub' claim
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must contain "sub"');

        $provisioningService->provisionUserFromJwtClaims($jwtClaims);
    }

    /**
     * Test: BenutzerProvider can load provisioned user.
     */
    public function testBenutzerProviderLoadsProvisionedUser(): void
    {
        $provisioningService = self::getContainer()->get(\App\Service\KeycloakUserProvisioningService::class);
        $benutzerProvider = self::getContainer()->get(\App\Security\User\BenutzerProvider::class);

        // Provision a user
        $jwtClaims = [
            'sub' => 'new-user-789',
            'email' => 'new.user@example.com',
            'given_name' => 'New',
            'family_name' => 'User'
        ];
        $provisioned = $provisioningService->provisionUserFromJwtClaims($jwtClaims);

        // Load user via provider
        $loaded = $benutzerProvider->loadUserByIdentifier('new-user-789');

        $this->assertNotNull($loaded);
        $this->assertEquals($provisioned->getId(), $loaded->getId());
        $this->assertEquals('new.user@example.com', $loaded->getEmail());
    }
}

