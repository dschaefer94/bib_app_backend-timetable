<?php

namespace App\Entity;

use App\Repository\CalendarSourceRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "klassen")]
class CalendarSource
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "klassen_id")]
    private ?int $id = null;

    #[ORM\Column(name: "klassenname", length: 100)]
    private string $className;

    #[ORM\Column(name: "ical_link", type: "string", length: 255, nullable: true)]
    private ?string $icalLink = null;

    #[ORM\Column(name: "last_synced_at", type: "datetime", nullable: true)]
    private ?\DateTime $lastSyncedAt = null;

    public function getId(): ?int { return $this->id; }
    public function getClassName(): string { return $this->className; }
    public function setClassName(string $className): self { $this->className = $className; return $this; }
    public function getIcalLink(): string { return $this->icalLink; }
    public function setIcalLink(string $icalLink): self { $this->icalLink = $icalLink; return $this; }
    public function getLastSyncedAt(): \DateTime { return $this->lastSyncedAt; }
    public function setLastSyncedAt(\DateTime $lastSyncedAt): self { $this->lastSyncedAt = $lastSyncedAt; return $this; }
}