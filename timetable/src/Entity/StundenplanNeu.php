<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use App\Repository\StundenplanNeuRepository; // Added this line

#[ORM\Entity(repositoryClass: StundenplanNeuRepository::class)]
#[ORM\Table(name: "stundenplan_neu")]
class StundenplanNeu
{
    #[ORM\Id]
    #[ORM\Column(type: "uuid", unique: true)]
    #[ORM\GeneratedValue(strategy: "CUSTOM")]
    #[ORM\CustomIdGenerator(class: "Symfony\\Bridge\\Doctrine\\IdGenerator\\UuidGenerator")]
    private ?Uuid $id = null;

    #[ORM\Column(type: "string", length: 255)]
    private string $summary;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: "datetime_immutable")]
    private \DateTimeImmutable $start;

    #[ORM\Column(name: '"end"', type: "datetime_immutable")] // Spaltenname in Anführungszeichen
    private \DateTimeImmutable $end;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(type: "string", length: 50, nullable: true)]
    private ?string $label = null;

    #[ORM\Column(type: "string", length: 50, nullable: true)] // Typ geändert
    private ?string $kategorie = null;

    #[ORM\Column(type: "json", nullable: true)]
    private ?array $originalEvent = null;

    #[ORM\Column(type: "datetime_immutable", nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: "string", length: 255)]
    private string $klasse; // Foreign Key für die Klasse

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getSummary(): string
    {
        return $this->summary;
    }

    public function setSummary(string $summary): self
    {
        $this->summary = $summary;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getStart(): \DateTimeImmutable
    {
        return $this->start;
    }

    public function setStart(\DateTimeImmutable $start): self
    {
        $this->start = $start;
        return $this;
    }

    public function getEnd(): \DateTimeImmutable
    {
        return $this->end;
    }

    public function setEnd(\DateTimeImmutable $end): self
    {
        $this->end = $end;
        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): self
    {
        $this->location = $location;
        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): self
    {
        $this->label = $label;
        return $this;
    }

    public function getKategorie(): ?string
    {
        return $this->kategorie;
    }

    public function setKategorie(?string $kategorie): self
    {
        $this->kategorie = $kategorie;
        return $this;
    }

    public function getOriginalEvent(): ?array
    {
        return $this->originalEvent;
    }

    public function setOriginalEvent(?array $originalEvent): self
    {
        $this->originalEvent = $originalEvent;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getKlasse(): string
    {
        return $this->klasse;
    }

    public function setKlasse(string $klasse): self
    {
        $this->klasse = $klasse;
        return $this;
    }
}
