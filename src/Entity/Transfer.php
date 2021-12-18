<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


/**
 * Transfer
 *
 * @ORM\Table(name="transfer")
 * @ORM\Entity
 */
class Transfer
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
     * @var Portfolio
     *
     * @ORM\ManyToOne(targetEntity="Portfolio")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="portfolio_from_id", referencedColumnName="id")
     * })
     */
    private $portfolioFrom;

    /**
     * @var Portfolio
     *
     * @ORM\ManyToOne(targetEntity="Portfolio")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="portfolio_to_id", referencedColumnName="id")
     * })
     */
    private $portfolioTo;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="date", nullable=false)
     */
    private $date;

    /**
     * @var float
     *
     * @ORM\Column(name="amount", type="float", precision=10, scale=0, nullable=false)
     */
    private $amount;



    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): void
    {
        $this->date = $date;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): void
    {
        $this->amount = $amount;
    }

    public function getPortfolioFrom(): ?Portfolio
    {
        return $this->portfolioFrom;
    }

    public function setPortfolioFrom(?Portfolio $portfolioFrom): void
    {
        $this->portfolioFrom = $portfolioFrom;
    }

    public function getPortfolioTo(): ?Portfolio
    {
        return $this->portfolioTo;
    }

    public function setPortfolioTo(?Portfolio $portfolioTo): void
    {
        $this->portfolioTo = $portfolioTo;
    }


}
