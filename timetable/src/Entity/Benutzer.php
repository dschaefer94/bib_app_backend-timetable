<?php

namespace App\Entity;

use App\Repository\BenutzerRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: BenutzerRepository::class)]
#[ORM\Table(name: "benutzer")]
class Benutzer implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: "uuid", unique: true)]
    #[ORM\GeneratedValue(strategy: "CUSTOM")]
    #[ORM\CustomIdGenerator(class: "Symfony\\Bridge\\Doctrine\\IdGenerator\\UuidGenerator")]
    private ?Uuid $id = null;

    #[ORM\Column(type: "string", length: 255)]
    private string $passwort;

    #[ORM\Column(type: "string", length: 255, unique: true)]
    private string $email;

    #[ORM\Column(type: "boolean")]
    private bool $istadmin = false;

    #[ORM\Column(type: "json")]
    private array $roles = [];

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        if ($this->istadmin) {
            $roles[] = 'ROLE_ADMIN';
        }

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->passwort;
    }

    public function setPassword(string $passwort): self
    {
        $this->passwort = $passwort;
        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function isIstadmin(): bool
    {
        return $this->istadmin;
    }

    public function setIsAdmin(bool $istadmin): self
    {
        $this->istadmin = $istadmin;
        return $this;
    }
}
