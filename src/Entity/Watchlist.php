<?php

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;


#[ORM\Entity()]
class Watchlist
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    /**
     * @Serializer\Exclude()
     */
    #[ORM\ManyToOne(targetEntity: Portfolio::class)]
    #[ORM\JoinColumn(name: 'portfolio', referencedColumnName: 'id')]
    private Portfolio $portfolio;

    /**
     * @Serializer\Type("integer")
     * @Serializer\SerializedName("shareheadId")
     */
    #[ORM\Column(name: "sharehead_id", type: "integer", nullable: false)]
    private int $shareheadId;

    #[ORM\Column(name: "start_date", type: "date", nullable: false)]
    private DateTime $startDate;


    /**
     * @var string|null
     */
    private $title;


    public function __construct()
    {
        $this->startDate = new DateTime();
    }

    public function __toString()
    {
        return (string) $this->shareheadId;
    }


    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Portfolio
     */
    public function getPortfolio(): ?Portfolio
    {
        return $this->portfolio;
    }

    /**
     * @param Portfolio $portfolio
     */
    public function setPortfolio(Portfolio $portfolio): void
    {
        $this->portfolio = $portfolio;
    }

    /**
     * @return int
     */
    public function getShareheadId(): ?int
    {
        return $this->shareheadId;
    }

    /**
     * @param int $shareheadId
     */
    public function setShareheadId(int $shareheadId): void
    {
        $this->shareheadId = $shareheadId;
    }

    /**
     * @return DateTime
     */
    public function getStartDate(): DateTime
    {
        return $this->startDate;
    }

    /**
     * @param DateTime $startDate
     */
    public function setStartDate(DateTime $startDate): void
    {
        $this->startDate = $startDate;
    }

    /**
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param string|null $title
     */
    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

}
