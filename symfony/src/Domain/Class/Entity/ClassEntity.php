<?php

namespace App\Domain\Class\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity]
#[ORM\Table(name: '`klassen`')]
class ClassEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $klassenId = null;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $klassenname;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $icalLink = null;

    public function __construct(string $klassenname, ?string $icalLink = null)
    {
        $this->klassenname = $klassenname;
        $this->icalLink = $icalLink;
    }

    public function getKlassenId(): ?int
    {
        return $this->klassenId;
    }

    public function getKlassenname(): string
    {
        return $this->klassenname;
    }

    public function setKlassenname(string $klassenname): void
    {
        $this->klassenname = $klassenname;
    }

    public function getIcalLink(): ?string
    {
        return $this->icalLink;
    }

    public function setIcalLink(?string $icalLink): void
    {
        $this->icalLink = $icalLink;
    }
}

