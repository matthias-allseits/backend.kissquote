<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * Transaction
 *
 * @ORM\Table(name="transaction", indexes={@ORM\Index(name="positionactionindex", columns={"position_id"})})
 * @ORM\Entity
 */
class Transaction
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
     * @var Position
     * @Serializer\Type("App\Entity\Position")
     *
     * @ORM\ManyToOne(targetEntity="Position")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="position_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $position;

    /**
     * @var Currency
     * @Serializer\Type("App\Entity\Currency")
     *
     * @ORM\ManyToOne(targetEntity="Currency")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="currency_id", referencedColumnName="id")
     * })
     */
    private $currency;

    /**
     * @var string
     * @Serializer\Type("string")
     *
     * @ORM\Column(name="title", type="string", length=64, nullable=true)
     */
    private $title;

    /**
     * @var \DateTime
     * @Serializer\Type("DateTime<'Y-m-d'>")
     *
     * @ORM\Column(name="date", type="date", nullable=false)
     */
    private $date;

    /**
     * @var integer
     * @Serializer\Type("integer")
     *
     * @ORM\Column(name="quantity", type="integer", nullable=false)
     */
    private $quantity;

    /**
     * @var float
     * @Serializer\Type("float")
     *
     * @ORM\Column(name="rate", type="float", precision=10, scale=0, nullable=false)
     */
    private $rate;

    /**
     * @var float
     * @Serializer\Type("float")
     *
     * @ORM\Column(name="fee", type="float", precision=10, scale=0, nullable=true)
     */
    private $fee;


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
     * @return Position|null
     */
    public function getPosition(): ?Position
    {
        return $this->position;
    }

    /**
     * @param Position $position
     */
    public function setPosition(Position $position): void
    {
        $this->position = $position;
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
     * @return int|null
     */
    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    /**
     * @param int $quantity
     */
    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
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
    public function getFee(): ?float
    {
        return $this->fee;
    }

    /**
     * @param float|null $fee
     */
    public function setFee(?float $fee): void
    {
        $this->fee = $fee;
    }

}
