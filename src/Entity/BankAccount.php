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
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    private $name;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Position", mappedBy="portfolio")
     */
    private $positions;

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


    public function __construct()
    {
        $this->positions = new ArrayCollection();
        $this->transfersFrom = new ArrayCollection();
        $this->transfersTo = new ArrayCollection();
    }


    public function __toString()
    {
        return $this->name;
    }


    public function getSummedInvestment(): float
    {
        $value = 0;
        if (null !== $this->positions) {
            foreach($this->positions as $position) {
                $value += $position->getSummedInvestment();
            }
        }

        return $value;
    }


    public function getActualValue(): float
    {
        $value = 0;
        if (null !== $this->positions) {
            foreach($this->positions as $position) {
                $value += $position->getActualValue();
            }
        }

        return $value;
    }


    public function getTransferBalance(): float
    {
        $balance = 0;
        foreach($this->transfersFrom as $transfer) {
            $balance += $transfer->getAmount();
        }
        foreach($this->transfersTo as $transfer) {
            $balance -= $transfer->getAmount();
        }

        return $balance;
    }


    /**
     * @return integer
     */
    public function getId(): int
    {
        return $this->id;
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
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Collection|Position[]
     */
    public function getPositions(): Collection
    {
        return $this->positions;
    }

    public function addPosition(Position $position): void
    {
        if (!$this->positions->contains($position)) {
            $this->positions[] = $position;
            $position->setPortfolio($this);
        }
    }

    public function removePosition(Position $position): void
    {
        if ($this->positions->contains($position)) {
            $this->positions->removeElement($position);
            // set the owning side to null (unless already changed)
            if ($position->getPortfolio() == $this) {
                $position->setPortfolio(null);
            }
        }
    }

    /**
     * @return Collection|Transfer[]
     */
    public function getTransfersFrom(): Collection
    {
        return $this->transfersFrom;
    }

    public function addTransfersFrom(Transfer $transfersFrom): void
    {
        if (!$this->transfersFrom->contains($transfersFrom)) {
            $this->transfersFrom[] = $transfersFrom;
            $transfersFrom->setPortfolioFrom($this);
        }
    }

    public function removeTransfersFrom(Transfer $transfersFrom): self
    {
        if ($this->transfersFrom->contains($transfersFrom)) {
            $this->transfersFrom->removeElement($transfersFrom);
            // set the owning side to null (unless already changed)
            if ($transfersFrom->getPortfolioFrom() === $this) {
                $transfersFrom->setPortfolioFrom(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Transfer[]
     */
    public function getTransfersTo(): Collection
    {
        return $this->transfersTo;
    }

    public function addTransfersTo(Transfer $transfersTo): self
    {
        if (!$this->transfersTo->contains($transfersTo)) {
            $this->transfersTo[] = $transfersTo;
            $transfersTo->setPortfolioTo($this);
        }

        return $this;
    }

    public function removeTransfersTo(Transfer $transfersTo): self
    {
        if ($this->transfersTo->contains($transfersTo)) {
            $this->transfersTo->removeElement($transfersTo);
            // set the owning side to null (unless already changed)
            if ($transfersTo->getPortfolioTo() === $this) {
                $transfersTo->setPortfolioTo(null);
            }
        }

        return $this;
    }

}
