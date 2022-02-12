<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


/**
 * Stockrate
 *
 * @ORM\Table(name="users_share_stockrate")
 * @ORM\Entity
 */
class UsersShareStockrate
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
     * @var Marketplace|null
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Marketplace")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="marketplace_id", referencedColumnName="id", nullable=true)
     * })
     */
    private $marketplace;

    /**
     * @var string
     *
     * @ORM\Column(name="currency_name", type="string", length=4, nullable=false)
     */
    private $currencyName;

    /**
     * @var string
     *
     * @ORM\Column(name="isin", type="string", length=16, nullable=false)
     */
    private $isin;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="date", nullable=false)
     */
    private $date;

    /**
     * @var float
     *
     * @ORM\Column(name="rate", type="float", precision=10, scale=3, nullable=false)
     */
    private $rate;


    public function __toString()
    {
        return $this->isin . ' has a rate of ' . $this->rate . ' in ' . $this->currencyName . ' at ' . $this->date->format('d.m.Y');
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
     * @return string
     */
    public function getCurrencyName(): string
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
     * @return string
     */
    public function getIsin(): string
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
     * @return \DateTime
     */
    public function getDate(): \DateTime
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
     * @return float
     */
    public function getRate(): float
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

}
