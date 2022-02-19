<?php

namespace App\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;


/**
 * SwissquoteShare
 *
 * @ORM\Table(name="swissquote_share")
 * @ORM\Entity
 */
class SwissquoteShare
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
     * @ORM\Column(name="currency", type="string", length=4, nullable=false)
     */
    private $currency;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=64, nullable=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="shortname", type="string", length=16, nullable=false)
     */
    private $shortname;

    /**
     * @var integer
     *
     * @ORM\Column(name="valor", type="integer", nullable=true)
     */
    private $valor;

    /**
     * @var string
     *
     * @ORM\Column(name="isin", type="string", length=16, nullable=false)
     */
    private $isin;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=255, nullable=true)
     */
    private $url;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=16, nullable=false)
     */
    private $type = 'stock';

    /**
     * @var string|null
     *
     * @ORM\Column(name="dividend_payment_method", type="string", length=16, nullable=true)
     */
    private $dividendPaymentMethod = 'yearly'; // or half-yearly or quarterly

    /**
     * @var string
     *
     * @ORM\Column(name="branche", type="string", length=64, nullable=true)
     */
    private $branche;

    /**
     * @var string|null
     *
     * @ORM\Column(name="headquarter", type="string", length=64, nullable=true)
     */
    private $headquarter;



	public function __toString()
    {
        return (string) $this->getName();
    }

    /**
     * @return int
     */
    public function getId(): int
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
    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     */
    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    /**
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getShortname(): ?string
    {
        return $this->shortname;
    }

    /**
     * @param string $shortname
     */
    public function setShortname(string $shortname): void
    {
        $this->shortname = $shortname;
    }

    /**
     * @return int
     */
    public function getValor(): int
    {
        return $this->valor;
    }

    /**
     * @param int $valor
     */
    public function setValor(int $valor): void
    {
        $this->valor = $valor;
    }

    /**
     * @return string
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
     * @return string
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * @param string|null $url
     */
    public function setUrl(?string $url): void
    {
        $this->url = $url;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return string|null
     */
    public function getDividendPaymentMethod(): ?string
    {
        return $this->dividendPaymentMethod;
    }

    /**
     * @param string|null $dividendPaymentMethod
     */
    public function setDividendPaymentMethod(?string $dividendPaymentMethod): void
    {
        $this->dividendPaymentMethod = $dividendPaymentMethod;
    }

    /**
     * @return string
     */
    public function getBranche(): string
    {
        return $this->branche;
    }

    /**
     * @param string $branche
     */
    public function setBranche(string $branche): void
    {
        $this->branche = $branche;
    }

    /**
     * @return string|null
     */
    public function getHeadquarter(): ?string
    {
        return $this->headquarter;
    }

    /**
     * @param string|null $headquarter
     */
    public function setHeadquarter(?string $headquarter): void
    {
        $this->headquarter = $headquarter;
    }

    /**
     * @return UsersShareStockrate[]|Collection
     */
    public function getRates()
    {
        return $this->rates;
    }

    /**
     * @param UsersShareStockrate[]|Collection $rates
     */
    public function setRates($rates): void
    {
        $this->rates = $rates;
    }

}
