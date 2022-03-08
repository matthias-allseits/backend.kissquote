<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;


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
     * @Serializer\Type("integer")
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
     * @Serializer\Type("string")
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    private $name;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Transfer", mappedBy="bankAccountFrom")
     */
    private $transfersFrom;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Transfer", mappedBy="bankAccountTo")
     */
    private $transfersTo;

    /**
     * @var Collection
     * @Serializer\Type("ArrayCollection<App\Entity\Position>")
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Position", mappedBy="bankAccount")
     */
    private $positions;


    public function __clone()
    {
        $this->id = null;
    }

    public function __toString(): string
    {
        return $this->getName() . ' (' . $this->getPortfolio() . ')';
    }

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

    /**
     * @param Position $position
     */
    public function addPosition(Position $position): void
    {
        $this->positions[] = $position;
    }

    /**
     * @param Position $bankAccount
     */
    public function removePosition(Position $bankAccount)
    {
        $this->positions->removeElement($bankAccount);
    }

    /**
     * @param Position[]|Collection $positions
     */
    public function setPositions($positions): void
    {
        $this->positions = $positions;
    }

    /**
     * @return Position[]
     */
    public function getPositions(): array
    {
        $positions = $this->positions->toArray();
        $sortArray = [];
        /** @var Position[] $positions */
        foreach($positions as $position) {
            $sortArray[] = $position->getActiveFrom();
        }
        array_multisort($sortArray, SORT_ASC, $positions);

        return $positions;
    }

}
