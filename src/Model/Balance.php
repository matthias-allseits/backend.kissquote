<?php

namespace App\Model;

use App\Entity\Position;

class Balance
{

    /** @var int */
    private $amount;

    /** @var float */
    private $firstRate;

    /** @var float */
    private $averageBuyPrice;

    /** @var int */
    private $investment;


    public function __construct(Position $position)
    {
        $this->amount = 13;
        $this->firstRate = 33.5;
        $this->averageBuyPrice = 31.5;
        $this->investment = 9898;
    }

    /**
     * @return int
     */
    public function getAmount(): int
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

    /**
     * @return float
     */
    public function getFirstRate(): float
    {
        return $this->firstRate;
    }

    /**
     * @param float $firstRate
     */
    public function setFirstRate(float $firstRate): void
    {
        $this->firstRate = $firstRate;
    }

    /**
     * @return float
     */
    public function getAverageBuyPrice(): float
    {
        return $this->averageBuyPrice;
    }

    /**
     * @param float $averageBuyPrice
     */
    public function setAverageBuyPrice(float $averageBuyPrice): void
    {
        $this->averageBuyPrice = $averageBuyPrice;
    }

    /**
     * @return int
     */
    public function getInvestment(): int
    {
        return $this->investment;
    }

    /**
     * @param int $investment
     */
    public function setInvestment(int $investment): void
    {
        $this->investment = $investment;
    }

}
