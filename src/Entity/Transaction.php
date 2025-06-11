<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;


#[ORM\Entity()]
#[ORM\Index(name: "positionactionindex", columns: ["position_id"])]
class Transaction
{
    const TITLES_POSITIVE = ['Einzahlung', 'Vergütung', 'Verkauf', 'Forex-Gutschrift', 'Fx-Gutschrift Comp.', 'Dividende', 'Kapitalrückzahlung', 'Capital Gain', 'Korrekturbuchung', 'Zins', 'Coupon'];
    const TITLES_NEGATIVE = ['Auszahlung', 'Kauf', 'Depotgebühren', 'Forex-Belastung', 'Fx-Belastung Comp.', 'Negativzins']; // todo: negativzinsen will not last forever!

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[Ignore]
    #[ORM\ManyToOne(targetEntity: Position::class, inversedBy: 'transactions')]
    #[ORM\JoinColumn(name: 'position_id', referencedColumnName: 'id', nullable: false)]
    private Position $position;

    #[ORM\ManyToOne(targetEntity: Currency::class)]
    #[ORM\JoinColumn(name: 'currency_id', referencedColumnName: 'id')]
    private Currency $currency;

    #[ORM\Column(name: "title", type: "string", length: 64, unique: false, nullable: true)]
    private ?string $title;

    #[Context([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d'])]
    #[ORM\Column(name: "date", type: "date", nullable: false)]
    private \DateTime $date;

    #[ORM\Column(name: "quantity", type: "float", precision: 10, scale: 0, nullable: false)]
    private float $quantity;

    #[ORM\Column(name: "rate", type: "float", precision: 10, scale: 0, nullable: false)]
    private float $rate;

    #[ORM\Column(name: "fee", type: "float", precision: 10, scale: 0, nullable: true)]
    private ?float $fee;


    public function __clone()
    {
        $this->id = null;
    }


    public function __toString()
    {
        return 'Transaction for position: ' . $this->position;
    }


    public function calculateTransactionCostsGross(): float
    {
        return ($this->getRate() * $this->getQuantity()) + $this->getFee();
    }


    public function calculateTransactionCostsNet(): float
    {
        return $this->getRate() * $this->getQuantity();
    }


    public function calculateCashValueNet(): float
    {
        if ($this->isPositive()) {
            return ($this->getRate() * $this->getQuantity()) - $this->getFee();
        } elseif ($this->isNegative()) {
            return ($this->getRate() * $this->getQuantity()) + $this->getFee();
        }

        return 0;
    }

    public function isPositive(): bool
    {
        return in_array($this->title, self::TITLES_POSITIVE);
    }

    public function isNegative(): bool
    {
        return in_array($this->title, self::TITLES_NEGATIVE);
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

    public function getPosition(): ?Position
    {
        return $this->position;
    }

    public function setPosition(?Position $position): void
    {
        $this->position = $position;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): void
    {
        $this->date = $date;
    }

    public function getQuantity(): ?float
    {
        return $this->quantity;
    }

    public function setQuantity(float $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function getRate(): ?float
    {
        return $this->rate;
    }

    public function setRate(float $rate): void
    {
        $this->rate = $rate;
    }

    public function getFee(): ?float
    {
        return $this->fee;
    }

    public function setFee(?float $fee): void
    {
        $this->fee = $fee;
    }

}
