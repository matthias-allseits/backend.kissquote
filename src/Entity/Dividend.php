<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Dividend
 *
 * @ORM\Table(name="dividend", indexes={@ORM\Index(name="shareindex", columns={"share_id"})})
 * @ORM\Entity
 */
class Dividend
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
	 * @var Share
	 *
	 * @ORM\ManyToOne(targetEntity="Share")
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
     * @ORM\Column(name="value_net", type="float", precision=10, scale=0, nullable=false)
     */
    private $valueNet;

    /**
     * @var float
     *
     * @ORM\Column(name="value_gross", type="float", precision=10, scale=0, nullable=false)
     */
    private $valueGross;


    /**
     * @return integer
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Share|null
     */
    public function getShare(): ?Share
    {
        return $this->share;
    }

    /**
     * @param Share $share
     */
    public function setShare(Share $share): void
    {
        $this->share = $share;
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
     * @return float|null
     */
    public function getValueNet(): ?float
    {
        return $this->valueNet;
    }

    /**
     * @param float $valueNet
     */
    public function setValueNet(float $valueNet): void
    {
        $this->valueNet = $valueNet;
    }

    /**
     * @return float|null
     */
    public function getValueGross(): ?float
    {
        return $this->valueGross;
    }

    /**
     * @param float $valueGross
     */
    public function setValueGross(float $valueGross): void
    {
        $this->valueGross = $valueGross;
    }

}
