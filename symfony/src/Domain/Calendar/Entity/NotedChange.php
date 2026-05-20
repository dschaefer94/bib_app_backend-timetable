<?php

namespace App\Domain\Calendar\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTime;

#[ORM\Entity]
#[ORM\Table(name: '`gelesene_termine`')]
class NotedChange
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $benutzerId;

    #[ORM\Column(type: 'string', length: 255)]
    private string $terminId;

    public function __construct(string $benutzerId, string $terminId)
    {
        $this->benutzerId = $benutzerId;
        $this->terminId = $terminId;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBenutzerId(): string
    {
        return $this->benutzerId;
    }

    public function getTerminId(): string
    {
        return $this->terminId;
    }
}

