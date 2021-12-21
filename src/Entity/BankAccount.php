<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;


/**
 * BankAccout
 *
 * @ORM\Table(name="bank_account")
 * @ORM\Entity
 */
class BankAccount
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
     *   @ORM\JoinColumn(name="portfolio_id", referencedColumnName="id")
     * })
     */
    private $portfolio;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    private $name;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Transfer", mappedBy="portfolioFrom")
     */
    private $transfersFrom;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Transfer", mappedBy="portfolioTo")
     */
    private $transfersTo;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Portfolio|null
     */
    public function getPortfolio(): ?Portfolio
    {
        return $this->portfolio;
    }

    /**
     * @param Portfolio $portfolio
     */
    public function setPortfolio(Portfolio $portfolio): void
    {
        $this->portfolio = $portfolio;
    }

    /**
     * @return string|null
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
     * @return Collection
     */
    public function getTransfersFrom(): Collection
    {
        return $this->transfersFrom;
    }

    /**
     * @param Collection $transfersFrom
     */
    public function setTransfersFrom(Collection $transfersFrom): void
    {
        $this->transfersFrom = $transfersFrom;
    }

    /**
     * @return Collection
     */
    public function getTransfersTo(): Collection
    {
        return $this->transfersTo;
    }

    /**
     * @param Collection $transfersTo
     */
    public function setTransfersTo(Collection $transfersTo): void
    {
        $this->transfersTo = $transfersTo;
    }

}
