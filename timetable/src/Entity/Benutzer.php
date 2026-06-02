<?php

namespace App\Entity;

use App\Repository\BenutzerRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: BenutzerRepository::class)]
#[ORM\Table(name: "benutzer")]
class Benutzer implements UserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: "uuid", unique: true)]
    #[ORM\GeneratedValue(strategy: "CUSTOM")]
    #[ORM\CustomIdGenerator(class: "Symfony\\Bridge\\Doctrine\\IdGenerator\\UuidGenerator")]
    private ?Uuid $id = null;

    #[ORM\Column(type: "string", length: 255, unique: true)]
    private string $identityId; // Dies ist die 'sub' ID von Keycloak/Cognito

    #[ORM\Column(type: "string", length: 255, unique: true)]
    private string $email;

    #[ORM\Column(type: "boolean")]
    private bool $istadmin = false;

    #[ORM\Column(type: "json")]
    private array $roles = [];

    #[ORM\OneToOne(mappedBy: 'benutzer', targetEntity: PersoenlicheDaten::class)] // 'cascade' entfernt
    private ?PersoenlicheDaten $persoenlicheDaten = null;

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

    public function getIdentityId(): string
    {
        return $this->identityId;
    }

    public function setIdentityId(string $identityId): self
    {
        $this->identityId = $identityId;
        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->identityId;
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

    public function getPersoenlicheDaten(): ?PersoenlicheDaten
    {
        return $this->persoenlicheDaten;
    }

    public function setPersoenlicheDaten(?PersoenlicheDaten $persoenlicheDaten): self
    {
        // unset the owning side of the relation if necessary
        if (null === $persoenlicheDaten && null !== $this->persoenlicheDaten) {
            $this->persoenlicheDaten->setBenutzer(null);
        }

        // set the owning side of the relation if necessary
        if (null !== $persoenlicheDaten && $persoenlicheDaten->getBenutzer() !== $this) {
            $persoenlicheDaten->setBenutzer($this);
        }

        $this->persoenlicheDaten = $persoenlicheDaten;

        return $this;
    }
}
