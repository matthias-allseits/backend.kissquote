<?php

namespace App\Entity;

use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;


/**
 * Watchlist
 *
 * @ORM\Table(name="watchlist")
 * @ORM\Entity
 */
class Watchlist
{

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var Portfolio
     *
     * @ORM\ManyToOne(targetEntity="Portfolio")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="portfolio_id", referencedColumnName="id")
     * })
     */
    private $portfolio;

    /**
     * @var integer
     * @Serializer\Type("integer")
     * @Serializer\SerializedName("shareheadId")
     *
     * @ORM\Column(name="sharehead_id", type="integer", nullable=false)
     */
    private $shareheadId;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="start_date", type="date", nullable=false)
     */
    private $startDate;


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

}
