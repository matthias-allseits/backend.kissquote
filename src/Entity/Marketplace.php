<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity()]
class Marketplace
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(name: "name", type: "string", length: 64, unique: false, nullable: false)]
    private string $name;

    #[ORM\Column(name: "place", type: "string", length: 64, unique: false, nullable: false)]
    private string $place;

    #[ORM\Column(name: "currency", type: "string", length: 4, unique: false, nullable: false)]
    private string $currency;

    #[ORM\Column(name: "url_key", type: "string", length: 5, unique: false, nullable: false)]
    private string $urlKey;

    #[ORM\Column(name: "isin_key", type: "string", length: 4, unique: false, nullable: false)]
    private string $isinKey;


    // todo: find out, why this is here necessary for put-position endpoint
    public function __construct()
    {
        $this->id = 1;
    }

    public function __toString()
    {
        return $this->name . ' ' . $this->getPlace();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getPlace(): ?string
    {
        return $this->place;
    }

    public function setPlace(string $place): void
    {
        $this->place = $place;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    public function getUrlKey(): ?string
    {
        return $this->urlKey;
    }

    public function setUrlKey(string $urlKey): void
    {
        $this->urlKey = $urlKey;
    }

    public function getIsinKey(): ?string
    {
        return $this->isinKey;
    }

    public function setIsinKey(string $isinKey): void
    {
        $this->isinKey = $isinKey;
    }

}
