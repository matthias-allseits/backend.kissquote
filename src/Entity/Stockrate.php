<?php

namespace App\Entity;

use App\Repository\StockrateRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;


#[ORM\Entity(repositoryClass: StockrateRepository::class)]
#[ORM\Index(name: "isin_idx", columns: ["isin"])]
#[ORM\Index(name: "currency_idx", columns: ["currency_name"])]
class Stockrate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[Ignore]
    #[ORM\ManyToOne(targetEntity: Marketplace::class)]
    #[JoinColumn(name: 'marketplace_id', referencedColumnName: 'id', nullable: true)]
    private ?Marketplace $marketplace;

    #[ORM\Column(name: "currency_name", type: "string", length: 4, unique: false, nullable: false)]
    private string $currencyName;

    #[ORM\Column(name: "isin", type: "string", length: 16, unique: false, nullable: false)]
    private string $isin;

    #[Context([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d'])]
    #[ORM\Column(name: "date", type: "date", nullable: false)]
    private \DateTime $date;

    #[ORM\Column(name: "rate", type: "float", precision: 10, scale: 3, nullable: false)]
    private ?float $rate = null;

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

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getMarketplace(): ?Marketplace
    {
        return $this->marketplace;
    }

    public function setMarketplace(?Marketplace $marketplace): void
    {
        $this->marketplace = $marketplace;
    }

    public function getCurrencyName(): ?string
    {
        return $this->currencyName;
    }

    public function setCurrencyName(string $currencyName): void
    {
        $this->currencyName = $currencyName;
    }

    public function getIsin(): ?string
    {
        return $this->isin;
    }

    public function setIsin(string $isin): void
    {
        $this->isin = $isin;
    }

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): void
    {
        $this->date = $date;
    }

    public function getRate(): ?float
    {
        return $this->rate;
    }

    public function setRate(float $rate): void
    {
        $this->rate = $rate;
    }

    public function getHigh(): ?float
    {
        return $this->high;
    }

    public function setHigh(float $high): void
    {
        $this->high = $high;
    }

    public function getLow(): ?float
    {
        return $this->low;
    }

    public function setLow(float $low): void
    {
        $this->low = $low;
    }

}
