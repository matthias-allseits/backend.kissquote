<?php

namespace App\Model;

use App\Entity\Position;
use App\Entity\Stockrate;


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

    /** @var string */
    private $collectedDividendsCurrency;

    /** @var int */
    private $projectedNextDividendPayment;

    /** @var string */
    private $projectedNextDividendCurrency;

    /** @var Stockrate|null */
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
        $this->collectedDividendsCurrency = $position->getCollectedDividendsCurrency();
        $this->projectedNextDividendPayment = $position->calculateNextDividendPayment();
        $this->projectedNextDividendCurrency = $position->getLastDividendTransaction() ? $position->getLastDividendTransaction()->getCurrency()->getName() : null;

        $this->lastRate = null;
    }

    /**
     * @param Stockrate|null $lastRate
     */
    public function setLastRate(?Stockrate $lastRate): void
    {
        $this->lastRate = $lastRate;
    }

}
