<?php

namespace App\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;


/**
 * Share
 *
 * @ORM\Table(name="share")
 * @ORM\Entity
 */
class Share
{
	/**
	 * @var integer
     * @Serializer\Type("integer")
	 *
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="IDENTITY")
	 */
	private $id;

    /**
     * @var Currency
     *
     * @ORM\ManyToOne(targetEntity="Currency")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="currency_id", referencedColumnName="id")
     * })
     */
    private $currency;

    /**
     * @var int
     *
     * @ORM\Column(name="portfolio_id", type="integer", nullable=true)
     */
    private $portfolioId;

    /**
     * @var Marketplace|null
     * @Serializer\Type("App\Entity\Marketplace")
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Marketplace")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="marketplace_id", referencedColumnName="id", nullable=true)
     * })
     */
    private $marketplace;

    /**
     * @var string
     * @Serializer\Type("string")
     *
     * @ORM\Column(name="name", type="string", length=64, nullable=false)
     */
    private $name;

    /**
     * @var string
     * @Serializer\Type("string")
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
     * @Serializer\Type("string")
     *
     * @ORM\Column(name="isin", type="string", length=16, nullable=false)
     */
    private $isin;

    /**
     * @var string
     * @Serializer\Type("string")
     *
     * @ORM\Column(name="type", type="string", length=16, nullable=false)
     */
    private $type = 'stock';

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

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Position", mappedBy="share", cascade={"remove"})
     */
    private $positions;


    public function __clone()
    {
        $this->id = null;
    }


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
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return Currency|null
     */
    public function getCurrency(): ?Currency
    {
        return $this->currency;
    }

    /**
     * @param Currency|null $currency
     */
    public function setCurrency(?Currency $currency): void
    {
        $this->currency = $currency;
    }

    /**
     * @return int|null
     */
    public function getPortfolioId(): ?int
    {
        return $this->portfolioId;
    }

    /**
     * @param int $portfolioId
     */
    public function setPortfolioId(int $portfolioId): void
    {
        $this->portfolioId = $portfolioId;
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
     * @return string|null
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
     * @return int|null
     */
    public function getValor(): ?int
    {
        return $this->valor;
    }

    /**
     * @param int|null $valor
     */
    public function setValor(?int $valor): void
    {
        $this->valor = $valor;
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
    public function getBranche(): ?string
    {
        return $this->branche;
    }

    /**
     * @param string|null $branche
     */
    public function setBranche(?string $branche): void
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

}
