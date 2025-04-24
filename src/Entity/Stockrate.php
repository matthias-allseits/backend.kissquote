<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;


#[ORM\Entity()]
#[ORM\Index(name: "isin_idx", columns: ["isin"])]
class Stockrate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Marketplace::class)]
    #[JoinColumn(name: 'marketplace_id', referencedColumnName: 'id', nullable: true)]
    private ?Marketplace $marketplace;

    #[ORM\Column(name: "currency_name", type: "string", length: 4, unique: false, nullable: false)]
    private string $currencyName;

    #[ORM\Column(name: "isin", type: "string", length: 16, unique: false, nullable: false)]
    private string $isin;

    #[ORM\Column(name: "date", type: "date", nullable: false)]
    private \DateTime $date;

    #[ORM\Column(name: "rate", type: "float", precision: 10, scale: 3, nullable: false)]
    private float $rate;

    #[ORM\Column(name: "high", type: "float", precision: 10, scale: 3, nullable: true)]
    private $high;

    #[ORM\Column(name: "low", type: "float", precision: 10, scale: 3, nullable: true)]
    private $low;


    public function __toString()
    {
        return $this->isin . ' has a rate of ' . $this->rate . ' in ' . $this->currencyName . ' at ' . $this->date->format('d.m.Y') . ' (high: ' . $this->high . ', low: ' . $this->low . ')';
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Marketplace|null
     */
    public function getMarketplace(): ?Marketplace
    {
        return $this->marketplace;
    }

    /**
     * @param Marketplace|null $marketplace
     */
    public function setMarketplace(?Marketplace $marketplace): void
    {
        $this->marketplace = $marketplace;
    }

    /**
     * @return string|null
     */
    public function getCurrencyName(): ?string
    {
        return $this->currencyName;
    }

    /**
     * @param string $currencyName
     */
    public function setCurrencyName(string $currencyName): void
    {
        $this->currencyName = $currencyName;
    }

    /**
     * @return string|null
     */
    public function getIsin(): ?string
    {
        return $this->isin;
    }

    /**
     * @param string $isin
     */
    public function setIsin(string $isin): void
    {
        $this->isin = $isin;
    }

    /**
     * @return \DateTime|null
     */
    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    /**
     * @param \DateTime $date
     */
    public function setDate(\DateTime $date): void
    {
        $this->date = $date;
    }

    /**
     * @return float|null
     */
    public function getRate(): ?float
    {
        return $this->rate;
    }

    /**
     * @param float $rate
     */
    public function setRate(float $rate): void
    {
        $this->rate = $rate;
    }

    /**
     * @return float|null
     */
    public function getHigh(): ?float
    {
        return $this->high;
    }

    /**
     * @param float $high
     */
    public function setHigh(float $high): void
    {
        $this->high = $high;
    }

    /**
     * @return float|null
     */
    public function getLow(): ?float
    {
        return $this->low;
    }

    /**
     * @param float $low
     */
    public function setLow(float $low): void
    {
        $this->low = $low;
    }

}
