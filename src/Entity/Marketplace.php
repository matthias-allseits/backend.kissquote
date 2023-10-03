<?php

namespace App\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;


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
     * @Serializer\Type("integer")
	 *
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="IDENTITY")
	 */
	private $id;

    /**
     * @var string
     * @Serializer\Type("string")
     *
     * @ORM\Column(name="name", type="string", length=64, nullable=false)
     */
    private $name;

    /**
     * @var string
     * @Serializer\Type("string")
     *
     * @ORM\Column(name="place", type="string", length=64, nullable=false)
     */
    private $place;

    /**
     * @var string
     * @Serializer\Type("string")
     *
     * @ORM\Column(name="currency", type="string", length=4, nullable=false)
     */
    private $currency;

    /**
     * @var string
     * @Serializer\Type("string")
     *
     * @ORM\Column(name="url_key", type="string", length=5, nullable=false)
     */
    private $urlKey;

    /**
     * @var string
     * @Serializer\Type("string")
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

    public function getUrlKey(): ?string
    {
        return $this->urlKey;
    }

    public function setUrlKey(string $urlKey): void
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
