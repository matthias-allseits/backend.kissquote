<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity()]
class Label
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(name: "portfolio_id", type: "integer", nullable: true)]
    private ?int $portfolioId;

    #[ORM\Column(name: "name", type: "string", length: 64, unique: false, nullable: false)]
    private string $name;

    #[ORM\Column(name: "color", type: "string", length: 64, unique: false, nullable: true)]
    private ?string $color;


    public function __toString()
    {
        return $this->name;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
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

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): void
    {
        $this->color = $color;
    }

}
