<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(readOnly: true)]
#[ORM\Table(name: "dummyklasse_calendar_api")]
class CalendarEntry
{
    #[ORM\Id]
    #[ORM\Column(type: "guid")]
    private $id;

    #[ORM\Column(type: "string", length: 255)]
    private string $summary;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: "datetime")]
    private \DateTime $start;

    #[ORM\Column(type: "datetime")]
    private \DateTime $end;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(type: "string", length: 50, nullable: true)]
    private ?string $label = null;

    #[ORM\Column(type: "string", length: 50, nullable: true)]
    private ?string $kategorie = null;

    #[ORM\Column(type: "json", nullable: true)]
    private ?array $originalEvent = null;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?\DateTime $updatedAt = null;

    public function getId() { return $this->id; }
    public function getSummary(): string { return $this->summary; }
    public function getDescription(): ?string { return $this->description; }
    public function getStart(): \DateTime { return $this->start; }
    public function getEnd(): \DateTime { return $this->end; }
    public function getLocation(): ?string { return $this->location; }
    public function getLabel(): ?string { return $this->label; }
    public function getKategorie(): ?string { return $this->kategorie; }
    public function getOriginalEvent(): ?array { return $this->originalEvent; }
    public function getUpdatedAt(): ?\DateTime { return $this->updatedAt; }
}
