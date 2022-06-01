<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * Dividend
 *
 * @ORM\Table(name="manual_dividend", indexes={@ORM\Index(name="shareindex", columns={"share_id"})})
 * @ORM\Entity
 */
class ManualDividend
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
	 * @var Share
     * @Serializer\Type("App\Entity\Share")
	 *
	 * @ORM\ManyToOne(targetEntity="Share")
	 * @ORM\JoinColumns({
	 *   @ORM\JoinColumn(name="share_id", referencedColumnName="id", nullable=false)
	 * })
	 */
	private $share;

    /**
     * @var int
     * @Serializer\Type("integer")
     *
     * @ORM\Column(name="year", type="integer", nullable=false)
     */
    private $year;

    /**
     * @var int
     * @Serializer\Type("integer")
     *
     * @ORM\Column(name="amount", type="integer", nullable=false)
     */
    private $amount;


    public function __toString()
    {
        return (string) $this->share->getName() . ' has a manual-dividend of ' . $this->amount . ' for year ' . $this->year;
    }


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
     * @return int|null
     */
    public function getYear(): ?int
    {
        return $this->year;
    }

    /**
     * @param int $year
     */
    public function setYear(int $year): void
    {
        $this->year = $year;
    }

    /**
     * @return int|null
     */
    public function getAmount(): ?int
    {
        return $this->amount;
    }

    /**
     * @param int $amount
     */
    public function setAmount(int $amount): void
    {
        $this->amount = $amount;
    }

}
