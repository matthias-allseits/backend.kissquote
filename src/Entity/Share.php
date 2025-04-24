<?php

namespace App\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;


#[ORM\Entity()]
class Share
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Currency::class)]
    #[ORM\JoinColumn(name: 'currency_id', referencedColumnName: 'id')]
    private Currency $currency;

    #[ORM\Column(name: "portfolio_id", type: "integer", nullable: true)]
    private ?int $portfolioId;

    /**
     * @Serializer\Type("App\Entity\Marketplace")
     */
    #[ORM\ManyToOne(targetEntity: Marketplace::class)]
    #[ORM\JoinColumn(name: 'marketplace_id', referencedColumnName: 'id')]
    private ?Marketplace $marketplace;

    /**
     * @Serializer\Type("string")
     */
    #[ORM\Column(name: "name", type: "string", length: 64, unique: false, nullable: false)]
    private string $name;

    /**
     * @Serializer\Type("string")
     */
    #[ORM\Column(name: "shortname", type: "string", length: 16, unique: false, nullable: false)]
    private string $shortname;

    /**
     * @Serializer\Type("string")
     */
    #[ORM\Column(name: "isin", type: "string", length: 16, unique: false, nullable: false)]
    private string $isin;

    #[ORM\OneToMany(targetEntity: Position::class, mappedBy: "share", cascade: ["remove"])]
    private array|Collection $positions;

    #[ORM\OneToMany(targetEntity: ManualDividend::class, mappedBy: "share", cascade: ["remove", "persist"])]
    private array|Collection $manualDividends;


    public function __clone()
    {
        $this->id = null;
    }


	public function __toString()
    {
        return (string) $this->getName();
    }

    public function hasActivePosition(): bool
    {
        foreach($this->getPositions() as $position) {
            if ($position->isActive()) {

                return true;
            }
        }

        return false;
    }

    public function getSwissquoteUrl(): string
    {
        $currency = $this->getCurrency()->getName();
        if ($currency == 'GBP') { // island apes...
            $currency = 'GBX';
        }
        $url = 'https://www.swissquote.ch/sq_mi/public/market/Detail.action?s=' . $this->getIsin() . '_' . $this->getMarketplace()->getUrlKey() . '_' . $currency;

        return $url;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
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
     * @return Position[]
     */
    public function getPositions(): array
    {
        $positions = $this->positions->toArray();

        return $positions;
    }

    /**
     * @param Position[] $positions
     */
    public function setPositions(array $positions): void
    {
        $this->positions = $positions;
    }

    /**
     * @return ManualDividend[]|Collection
     */
    public function getManualDividends()
    {
        return $this->manualDividends;
    }

    /**
     * @param ManualDividend[]|Collection $manualDividends
     */
    public function setManualDividends($manualDividends): void
    {
        $this->manualDividends = $manualDividends;
    }

}
