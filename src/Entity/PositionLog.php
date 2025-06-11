<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Ignore;

#[ORM\Entity()]
class PositionLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[Ignore]
    #[ORM\ManyToOne(targetEntity: Position::class, inversedBy: 'logEntries')]
    #[ORM\JoinColumn(name: 'position_id', referencedColumnName: 'id', nullable: false)]
    private Position $position;

    #[ORM\Column(name: "date", type: "date", nullable: false)]
    private \DateTime $date;

    #[ORM\Column(name: "log", type: "string", length: 256, unique: false, nullable: false)]
    private string $log;

    #[ORM\Column(name: "emoticon", type: "string", length: 8, unique: false, nullable: true)]
    private ?string $emoticon;

    #[ORM\Column(name: "demo", type: "boolean", nullable: false)]
    private bool $demo = true;

    #[ORM\Column(name: "pinned", type: "boolean", nullable: false)]
    private bool $pinned = false;

    private int $positionId;


    public function __clone()
    {
        $this->id = null;
    }


    public function __toString()
    {
        return 'PositionLogEntry for position: ' . $this->position;
    }


    public function getId(): int
    {
        return $this->id;
    }

    public function getPosition(): ?Position
    {
        return $this->position;
    }

    public function setPosition(?Position $position): void
    {
        $this->position = $position;
    }

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): void
    {
        $this->date = $date;
    }

    public function getLog(): ?string
    {
        return $this->log;
    }

    public function setLog(string $log): void
    {
        $this->log = $log;
    }

    public function getEmoticon(): ?string
    {
        return $this->emoticon;
    }

    public function setEmoticon(?string $emoticon): void
    {
        $this->emoticon = $emoticon;
    }

    public function isDemo(): bool
    {
        return $this->demo;
    }

    public function setDemo(bool $demo): void
    {
        $this->demo = $demo;
    }

    public function isPinned(): bool
    {
        return $this->pinned;
    }

    public function setPinned(bool $pinned): void
    {
        $this->pinned = $pinned;
    }

}
