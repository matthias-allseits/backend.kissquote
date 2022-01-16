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


    public function __construct(Position $position)
    {
        $this->amount = $position->getCountOfSharesByDate();
        $this->firstRate = $position->getTransactions()[0]->getRate();
        $this->averagePayedPriceGross = $position->getAveragePayedPriceGross();
        $this->averagePayedPriceNet = $position->getAveragePayedPriceNet();
        $this->investment = $position->getSummedInvestmentGross();
        $this->transactionFeesTotal = $position->getSummedFees();
        $this->collectedDividends = $position->getCollectedDividends();
        $this->projectedNextDividendPayment = $position->calculateNextDividendPayment();
    }

}
