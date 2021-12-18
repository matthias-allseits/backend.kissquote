<?php

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;


/**
 * Portfolio
 *
 * @ORM\Table(name="portfolio")
 * @ORM\Entity
 */
class Portfolio
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
     * @var string|null
     *
     * @ORM\Column(name="user_name", type="string", length=32, nullable=true, unique=true)
     */
    private $userName;

    /**
     * @var string|null
     *
     * @ORM\Column(name="hash_key", type="string", length=32, nullable=true, unique=true)
     */
    private $hashKey;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="start_date", type="date", nullable=false)
     */
    private $startDate;


    public function __construct()
    {
        $this->startDate = new DateTime();
    }

    public function __toString()
    {
        return (string) $this->userName;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getUserName(): ?string
    {
        return $this->userName;
    }

    /**
     * @param string|null $userName
     */
    public function setUserName(?string $userName): void
    {
        $this->userName = $userName;
    }

    /**
     * @return string|null
     */
    public function getHashKey(): ?string
    {
        return $this->hashKey;
    }

    /**
     * @param string|null $hashKey
     */
    public function setHashKey(?string $hashKey): void
    {
        $this->hashKey = $hashKey;
    }

    /**
     * @return DateTime
     */
    public function getStartDate(): DateTime
    {
        return $this->startDate;
    }

    /**
     * @param DateTime $startDate
     */
    public function setStartDate(DateTime $startDate): void
    {
        $this->startDate = $startDate;
    }

}
