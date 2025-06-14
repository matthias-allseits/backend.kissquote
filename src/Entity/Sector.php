<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity()]
class Sector
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
	private int $id;

    #[ORM\Column(name: "portfolio_id", type: "integer", nullable: true)]
    private ?int $portfolioId;

    #[ORM\Column(name: "name", type: "string", length: 64, unique: false, nullable: false)]
    private string $name;


    // todo: find out, why this is here necessary for put-position endpoint
    public function __construct()
    {
        $this->id = 1;
    }


    public function __clone()
    {
        $this->id = null;
    }


    public function __toString()
    {
        return $this->name;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPortfolioId(): ?int
    {
        return $this->portfolioId;
    }

    public function setPortfolioId(int $portfolioId): void
    {
        $this->portfolioId = $portfolioId;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

}
