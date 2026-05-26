<?php

namespace App\Entity;

use App\Repository\PersoenlicheDatenRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: PersoenlicheDatenRepository::class)]
#[ORM\Table(name: "persoenliche_daten")]
class PersoenlicheDaten
{
    #[ORM\Id]
    #[ORM\Column(type: "uuid", unique: true)]
    #[ORM\GeneratedValue(strategy: "CUSTOM")]
    #[ORM\CustomIdGenerator(class: "Symfony\\Bridge\\Doctrine\\IdGenerator\\UuidGenerator")]
    private ?Uuid $id = null;

    #[ORM\OneToOne(targetEntity: Benutzer::class, inversedBy: 'persoenlicheDaten')] // 'cascade' entfernt
    #[ORM\JoinColumn(name: "benutzer_id", referencedColumnName: "id", nullable: false)]
    private ?Benutzer $benutzer = null;

    #[ORM\Column(type: "string", length: 100)]
    private string $name;

    #[ORM\Column(type: "string", length: 100)]
    private string $vorname;

    #[ORM\ManyToOne(targetEntity: CalendarSource::class)]
    #[ORM\JoinColumn(name: "klassen_id", referencedColumnName: "klassen_id", nullable: true)]
    private ?CalendarSource $klasse = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getBenutzer(): ?Benutzer
    {
        return $this->benutzer;
    }

    public function setBenutzer(Benutzer $benutzer): self
    {
        $this->benutzer = $benutzer;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getVorname(): string
    {
        return $this->vorname;
    }

    public function setVorname(string $vorname): self
    {
        $this->vorname = $vorname;
        return $this;
    }

    public function getKlasse(): ?CalendarSource
    {
        return $this->klasse;
    }

    public function setKlasse(?CalendarSource $klasse): self
    {
        $this->klasse = $klasse;
        return $this;
    }
}
