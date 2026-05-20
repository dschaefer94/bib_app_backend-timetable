<?php

namespace App\Domain\User\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Domain\Class\Entity\ClassEntity;

#[ORM\Entity]
#[ORM\Table(name: '`persoenliche_daten`')]
class PersonalData
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 255)]
    private string $benutzerId;

    #[ORM\OneToOne(inversedBy: 'personalData', targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'benutzer_id', referencedColumnName: 'benutzer_id', onDelete: 'CASCADE')]
    private User $benutzer;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 255)]
    private string $vorname;

    #[ORM\ManyToOne(targetEntity: ClassEntity::class)]
    #[ORM\JoinColumn(name: 'klassen_id', referencedColumnName: 'klassen_id', nullable: true, onDelete: 'SET NULL')]
    private ?ClassEntity $klasse = null;

    public function __construct(string $benutzerId, string $name, string $vorname)
    {
        $this->benutzerId = $benutzerId;
        $this->name = $name;
        $this->vorname = $vorname;
    }

    public function getBenutzerId(): string
    {
        return $this->benutzerId;
    }

    public function getBenutzer(): User
    {
        return $this->benutzer;
    }

    public function setBenutzer(User $benutzer): void
    {
        $this->benutzer = $benutzer;
        $this->benutzerId = $benutzer->getBenutzerId();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getVorname(): string
    {
        return $this->vorname;
    }

    public function setVorname(string $vorname): void
    {
        $this->vorname = $vorname;
    }

    public function getKlasse(): ?ClassEntity
    {
        return $this->klasse;
    }

    public function setKlasse(?ClassEntity $klasse): void
    {
        $this->klasse = $klasse;
    }
}

