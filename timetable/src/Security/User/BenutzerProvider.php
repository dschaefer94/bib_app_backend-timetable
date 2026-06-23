<?php

namespace App\Security\User;

use App\Entity\Benutzer;
use App\Repository\BenutzerRepository;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class BenutzerProvider implements UserProviderInterface
{
    private BenutzerRepository $benutzerRepository;

    public function __construct(BenutzerRepository $benutzerRepository)
    {
        $this->benutzerRepository = $benutzerRepository;
    }

    /**
     * Loads the user for the given user identifier (identityId/sub claim).
     * User should already exist after JWT Authenticator's provisioning step.
     */
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $benutzer = $this->benutzerRepository->findOneBy(['identityId' => $identifier]);

        if (!$benutzer) {
            throw new UserNotFoundException(sprintf('User with identityId "%s" not found. User should have been provisioned during authentication.', $identifier));
        }

        return $benutzer;
    }

    /**
     * @deprecated since Symfony 5.3, loadUserByIdentifier() is the preferred way
     */
    public function loadUserByUsername(string $username): UserInterface
    {
        return $this->loadUserByIdentifier($username);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof Benutzer) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        // The user is reloaded from the database to ensure their data is up-to-date.
        // In a stateless JWT setup, this method might not be called often,
        // but it's good practice to implement it correctly.
        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return Benutzer::class === $class || is_subclass_of($class, Benutzer::class);
    }
}
