<?php

namespace App\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Ignore;


#[ORM\Entity()]
class BankAccount
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[Ignore]
    #[ORM\ManyToOne(targetEntity: Portfolio::class, inversedBy: 'bankAccounts')]
    #[ORM\JoinColumn(name: 'portfolio_id', referencedColumnName: 'id')]
    private Portfolio $portfolio;

    #[ORM\Column(name: "name", type: "string", length: 255, unique: false, nullable: false)]
    private string $name;

    #[ORM\OneToMany(targetEntity: Position::class, mappedBy: "bankAccount")]
    private array|Collection $positions;


    public function getCashPositionByCurrency(Currency $currency): ?Position
    {
        foreach($this->getPositions() as $position) {
            if ($position->isCash() && $position->getCurrency()->getId() == $currency->getId()) {

                return $position;
            }
        }

        return null;
    }


    public function __clone() {
        $this->id = null;
        $newPositions = [];
        foreach($this->getPositions() as $position) {
            $newPosition = clone $position;
            $newPosition->setBankAccount($this);
//            $newPosition->setTransactions([]);

            $share = $position->getShare();
            $newShare = null;
            if (null !== $share) {
                $newShare = clone $share;
//                $newShare->setPortfolio($this->portfolio);
            }
            $newPosition->setShare($newShare);
            $newPositions[] = $newPosition;
        }
        $this->setPositions($newPositions);
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
        if (is_array($this->positions)) {
            $positions = $this->positions;
        } else {
            $positions = $this->positions->toArray();
        }
        $sortArray = [];
        /** @var Position[] $positions */
        foreach ($positions as $position) {
            $sortArray[] = $position->getActiveFrom();
        }
        array_multisort($sortArray, SORT_ASC, $positions);

        return $positions;
    }

}
