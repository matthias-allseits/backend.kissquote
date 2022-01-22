<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


/**
 * Stockrate
 *
 * @ORM\Table(name="stockrate")
 * @ORM\Entity
 */
class Stockrate
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
     * @var SwissquoteShare
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\SwissquoteShare", inversedBy="rates")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="share_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $share;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="date", nullable=false)
     */
    private $date;

    /**
     * @var float
     *
     * @ORM\Column(name="rate", type="float", precision=10, scale=3, nullable=false)
     */
    private $rate;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=4, nullable=false)
     */
    private $currency;


    public function __toString()
    {
        return $this->share . ' has a rate of ' . $this->rate . ' in ' . $this->currency . ' at ' . $this->date->format('d.m.Y');
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getRate(): ?float
    {
        return $this->rate;
    }

    public function setRate(float $rate): self
    {
        $this->rate = $rate;

        return $this;
    }

    public function getShare(): ?SwissquoteShare
    {
        return $this->share;
    }

    public function setShare(?SwissquoteShare $share): self
    {
        $this->share = $share;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

}
