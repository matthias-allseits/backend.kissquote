<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;


#[ORM\Entity()]
class Currency
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
	private int $id;

    #[ORM\Column(name: "portfolio_id", type: "integer", nullable: true)]
    private ?int $portfolioId;

    /**
     * @Serializer\Type("string")
     */
    #[ORM\Column(name: "name", type: "string", length: 64, unique: false, nullable: false)]
    private string $name;

    /**
     * @Serializer\Type("float")
     */
    #[ORM\Column(name: "rate", type: "float", precision: 10, scale: 3, nullable: false)]
    private float $rate;


    public function __clone()
    {
        $this->id = null;
    }


    public function __toString()
    {
        return (string) $this->name;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
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

}
