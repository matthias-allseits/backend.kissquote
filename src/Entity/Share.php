<?php

namespace App\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Ignore;


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

    #[ORM\ManyToOne(targetEntity: Marketplace::class)]
    #[ORM\JoinColumn(name: 'marketplace_id', referencedColumnName: 'id')]
    private ?Marketplace $marketplace;

    #[ORM\Column(name: "name", type: "string", length: 64, unique: false, nullable: false)]
    private string $name;

    #[ORM\Column(name: "shortname", type: "string", length: 16, unique: false, nullable: false)]
    private string $shortname;

    #[ORM\Column(name: "isin", type: "string", length: 16, unique: false, nullable: false)]
    private string $isin;

    #[Ignore]
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

    public function getId(): int
    {
        return $this->id;
    }

    public function getCurrency(): ?Currency
    {
        return $this->currency;
    }

    public function setCurrency(?Currency $currency): void
    {
        $this->currency = $currency;
    }

    public function getPortfolioId(): ?int
    {
        return $this->portfolioId;
    }

    public function setPortfolioId(int $portfolioId): void
    {
        $this->portfolioId = $portfolioId;
    }

    public function getMarketplace(): ?Marketplace
    {
        return $this->marketplace;
    }

    public function setMarketplace(?Marketplace $marketplace): void
    {
        $this->marketplace = $marketplace;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getShortname(): ?string
    {
        return $this->shortname;
    }

    public function setShortname(string $shortname): void
    {
        $this->shortname = $shortname;
    }

    public function getIsin(): ?string
    {
        return $this->isin;
    }

    public function setIsin(string $isin): void
    {
        $this->isin = $isin;
    }

    public function getPositions(): array
    {
        $positions = $this->positions->toArray();

        return $positions;
    }

    public function setPositions(array $positions): void
    {
        $this->positions = $positions;
    }

    public function getManualDividends(): array
    {
        return $this->manualDividends;
    }

    public function setManualDividends(array $manualDividends): void
    {
        $this->manualDividends = $manualDividends;
    }

}
