<?php

namespace App\Service;

use App\Entity\Benutzer;
use App\Entity\PersoenlicheDaten;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for provisioning users from JWT claims (Keycloak/Cognito).
 * Automatically creates/updates users in the database based on JWT claims.
 */
class KeycloakUserProvisioningService
{
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    /**
     * Provision a user from JWT claims.
     * If user doesn't exist, creates a new one.
     * If user exists, updates email and personal data if available.
     *
     * @param array $claims Decoded JWT claims from Keycloak/Cognito
     *
     * @return Benutzer The provisioned user entity
     */
    public function provisionUserFromJwtClaims(array $claims): Benutzer
    {
        $identityId = $claims['sub'] ?? null;
        $email = $claims['email'] ?? null;
        $givenName = $claims['given_name'] ?? null;
        $familyName = $claims['family_name'] ?? null;

        if (!$identityId) {
            throw new \InvalidArgumentException('JWT claims must contain "sub" (identityId)');
        }

        // Try to find existing user
        $benutzer = $this->entityManager->getRepository(Benutzer::class)
            ->findOneBy(['identityId' => $identityId]);

        if (!$benutzer) {
            // Create new user
            $this->logger->info('Provisioning new user', ['identityId' => $identityId, 'email' => $email]);
            $benutzer = new Benutzer();
            $benutzer->setIdentityId($identityId);

            if ($email) {
                $benutzer->setEmail($email);
            } else {
                // Fallback email if not provided in JWT
                $benutzer->setEmail("user-{$identityId}@keycloak.local");
            }

            $this->entityManager->persist($benutzer);
        } else {
            // Update existing user with latest email if provided
            if ($email && $benutzer->getEmail() !== $email) {
                $this->logger->info('Updating user email', ['identityId' => $identityId, 'oldEmail' => $benutzer->getEmail(), 'newEmail' => $email]);
                $benutzer->setEmail($email);
            }
        }

        // Provision personal data if not exists
        $persoenlicheDaten = $benutzer->getPersoenlicheDaten();
        if (!$persoenlicheDaten && ($givenName || $familyName)) {
            $this->logger->info('Creating personal data for user', ['identityId' => $identityId]);
            $persoenlicheDaten = new PersoenlicheDaten();
            $persoenlicheDaten->setBenutzer($benutzer);
            $persoenlicheDaten->setVorname($givenName ?? 'User');
            $persoenlicheDaten->setName($familyName ?? 'Unknown');

            $this->entityManager->persist($persoenlicheDaten);
            $benutzer->setPersoenlicheDaten($persoenlicheDaten);
        } elseif ($persoenlicheDaten) {
            // Update existing personal data if names changed
            $updated = false;
            if ($givenName && $persoenlicheDaten->getVorname() !== $givenName) {
                $persoenlicheDaten->setVorname($givenName);
                $updated = true;
            }
            if ($familyName && $persoenlicheDaten->getName() !== $familyName) {
                $persoenlicheDaten->setName($familyName);
                $updated = true;
            }
            if ($updated) {
                $this->logger->info('Updating personal data for user', ['identityId' => $identityId]);
            }
        }

        // Extract and set roles if available in JWT
        $roles = $this->extractRolesFromJwtClaims($claims);
        if (!empty($roles)) {
            $benutzer->setRoles($roles);
        }

        $this->entityManager->flush();
        $this->logger->info('User provisioned successfully', ['identityId' => $identityId]);

        return $benutzer;
    }

    /**
     * Extract roles from JWT claims (supports both Keycloak and Cognito formats).
     * Keycloak: claims contain 'realm_access' → 'roles' or 'resource_access' → client_id → 'roles'
     * Cognito: claims contain 'cognito:groups'
     *
     * @param array $claims Decoded JWT claims
     *
     * @return array List of roles with ROLE_ prefix for Symfony
     */
    private function extractRolesFromJwtClaims(array $claims): array
    {
        $roles = [];

        // Keycloak realm roles
        if (isset($claims['realm_access']['roles']) && is_array($claims['realm_access']['roles'])) {
            foreach ($claims['realm_access']['roles'] as $role) {
                $roles[] = 'ROLE_' . strtoupper($role);
            }
        }

        // Keycloak client roles (assuming 'bib-app-backend' client)
        if (isset($claims['resource_access']['bib-app-backend']['roles']) && is_array($claims['resource_access']['bib-app-backend']['roles'])) {
            foreach ($claims['resource_access']['bib-app-backend']['roles'] as $role) {
                $roles[] = 'ROLE_' . strtoupper($role);
            }
        }

        // AWS Cognito groups
        if (isset($claims['cognito:groups']) && is_array($claims['cognito:groups'])) {
            foreach ($claims['cognito:groups'] as $group) {
                $roles[] = 'ROLE_' . strtoupper($group);
            }
        }

        // Ensure all roles have ROLE_ prefix
        $roles = array_map(function ($role) {
            return str_starts_with($role, 'ROLE_') ? $role : 'ROLE_' . strtoupper($role);
        }, $roles);

        // Default role if no roles found
        if (empty($roles)) {
            $roles[] = 'ROLE_USER';
        }

        return array_unique($roles);
    }
}

