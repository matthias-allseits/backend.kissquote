<?php

namespace App\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;


/**
 * Marketplace
 *
 * @ORM\Table(name="marketplace")
 * @ORM\Entity
 */
class Marketplace
{
	/**
	 * @var integer
	 *
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="IDENTITY")
	 */
	private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=64, nullable=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="place", type="string", length=64, nullable=false)
     */
    private $place;

    /**
     * @var string
     *
     * @ORM\Column(name="currency", type="string", length=4, nullable=false)
     */
    private $currency;

    /**
     * @var int
     *
     * @ORM\Column(name="url_key", type="smallint", nullable=false)
     */
    private $urlKey;

    /**
     * @var string
     *
     * @ORM\Column(name="isin_key", type="string", length=4, nullable=false)
     */
    private $isinKey;


    public function __toString()
    {
        return (string) $this->name . ' ' . $this->getPlace();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
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
     * @return string
     */
    public function getPlace(): ?string
    {
        return $this->place;
    }

    /**
     * @param string $place
     */
    public function setPlace(string $place): void
    {
        $this->place = $place;
    }

    /**
     * @return string
     */
    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     */
    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    /**
     * @return int
     */
    public function getUrlKey(): ?int
    {
        return $this->urlKey;
    }

    /**
     * @param int $urlKey
     */
    public function setUrlKey(int $urlKey): void
    {
        $this->urlKey = $urlKey;
    }

    /**
     * @return string
     */
    public function getIsinKey(): ?string
    {
        return $this->isinKey;
    }

    /**
     * @param string $isinKey
     */
    public function setIsinKey(string $isinKey): void
    {
        $this->isinKey = $isinKey;
    }

}
