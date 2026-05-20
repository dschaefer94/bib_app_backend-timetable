<?php

namespace App\Domain\User\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Shared\ValueObject\Email;

#[ORM\Entity]
#[ORM\Table(name: '`benutzer`')]
class User
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 255)]
    private string $benutzerId;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $email;

    #[ORM\Column(type: 'string', length: 255)]
    private string $passwort;

    #[ORM\Column(type: 'boolean')]
    private bool $istAdmin = false;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $resetToken = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $resetTokenExpires = null;

    #[ORM\OneToOne(mappedBy: 'benutzer', cascade: ['persist', 'remove'], targetEntity: PersonalData::class)]
    private ?PersonalData $personalData = null;

    public function __construct(string $benutzerId, Email $email, string $hashedPassword)
    {
        $this->benutzerId = $benutzerId;
        $this->email = $email->getValue();
        $this->passwort = $hashedPassword;
    }

    public function getBenutzerId(): string
    {
        return $this->benutzerId;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getPasswort(): string
    {
        return $this->passwort;
    }

    public function setPasswort(string $hashedPassword): void
    {
        $this->passwort = $hashedPassword;
    }

    public function isAdmin(): bool
    {
        return $this->istAdmin;
    }

    public function setAdmin(bool $istAdmin): void
    {
        $this->istAdmin = $istAdmin;
    }

    public function getResetToken(): ?string
    {
        return $this->resetToken;
    }

    public function setResetToken(?string $token, ?\DateTimeInterface $expires = null): void
    {
        $this->resetToken = $token;
        $this->resetTokenExpires = $expires;
    }

    public function getResetTokenExpires(): ?\DateTimeInterface
    {
        return $this->resetTokenExpires;
    }

    public function getPersonalData(): ?PersonalData
    {
        return $this->personalData;
    }

    public function setPersonalData(PersonalData $personalData): void
    {
        $this->personalData = $personalData;
        $personalData->setBenutzer($this);
    }
}

