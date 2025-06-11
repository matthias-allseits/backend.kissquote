<?php

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Ignore;


#[ORM\Entity()]
class Watchlist
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[Ignore]
    #[ORM\ManyToOne(targetEntity: Portfolio::class, inversedBy: 'watchlistEntries')]
    #[ORM\JoinColumn(name: 'portfolio_id', referencedColumnName: 'id')]
    private Portfolio $portfolio;

    #[ORM\Column(name: "sharehead_id", type: "integer", nullable: false)]
    private int $shareheadId;

    #[ORM\Column(name: "start_date", type: "date", nullable: false)]
    private DateTime $startDate;


    private ?string $title;


    public function __construct()
    {
        $this->startDate = new DateTime();
    }

    public function __toString()
    {
        return (string) $this->shareheadId;
    }


    public function getId(): int
    {
        return $this->id;
    }

    public function getPortfolio(): ?Portfolio
    {
        return $this->portfolio;
    }

    public function setPortfolio(Portfolio $portfolio): void
    {
        $this->portfolio = $portfolio;
    }

    public function getShareheadId(): ?int
    {
        return $this->shareheadId;
    }

    public function setShareheadId(int $shareheadId): void
    {
        $this->shareheadId = $shareheadId;
    }

    public function getStartDate(): DateTime
    {
        return $this->startDate;
    }

    public function setStartDate(DateTime $startDate): void
    {
        $this->startDate = $startDate;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

}
