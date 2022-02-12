<?php

namespace App\Model;

use App\Entity\Position;
use App\Entity\Stockrate;
use App\Entity\UsersShareStockrate;
use Doctrine\ORM\EntityManager;


class Balance
{

    /** @var int */
    private $amount;

    /** @var float */
    private $firstRate;

    /** @var float */
    private $averagePayedPriceGross;

    /** @var float */
    private $averagePayedPriceNet;

    /** @var int */
    private $investment;

    /** @var int */
    private $transactionFeesTotal;

    /** @var int */
    private $collectedDividends;

    /** @var int */
    private $projectedNextDividendPayment;

    /** @var UsersShareStockrate|null */
    private $lastRate;

    /** @var float */
    private $cashValue;


    public function __construct(Position $position)
    {
        $this->amount = $position->getCountOfSharesByDate();
        $this->firstRate = count($position->getTransactions()) > 0 ? $position->getTransactions()[0]->getRate() : null;
        if (false === $position->isCash()) {
            $this->averagePayedPriceGross = $position->getAveragePayedPriceGross();
            $this->averagePayedPriceNet = $position->getAveragePayedPriceNet();
        } else {
            $this->cashValue = $position->getCashValue();
        }
        $this->investment = $position->getSummedInvestmentGross();
        $this->transactionFeesTotal = $position->getSummedFees();
        $this->collectedDividends = $position->getCollectedDividends();
        $this->projectedNextDividendPayment = $position->calculateNextDividendPayment();

        $this->lastRate = null;
    }

    /**
     * @param UsersShareStockrate|null $lastRate
     */
    public function setLastRate(?UsersShareStockrate $lastRate): void
    {
        $this->lastRate = $lastRate;
    }

}
