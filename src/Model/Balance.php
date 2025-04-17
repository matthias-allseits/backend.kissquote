<?php

namespace App\Model;

use App\Entity\Position;
use App\Entity\Stockrate;


class Balance
{

    /** @var float */
    private $amount;

    /** @var float */
    private $firstRate;

    /** @var float */
    private $averagePayedPriceGross;

    /** @var float */
    private $averagePayedPriceNet;

    /** @var float */
    private $breakEvenPrice;

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

    /** @var int */
    private $collectedInterest;

    /** @var int */
    private $collectedCoupons;

    /** @var Stockrate|null */
    private $lastRate;

    /** @var float */
    private $cashValue;

    /** @var int */
    private $closedResult;

    /** @var float[] */
    private $performance = [];


    public function __construct(Position $position)
    {
        $this->amount = $position->getCountOfSharesByDate();
        $this->firstRate = count($position->getTransactions()) > 0 ? $position->getTransactions()[0]->getRate() : null;
        if (false === $position->isCash()) {
            $this->averagePayedPriceGross = $position->getAveragePayedPriceGross();
            $this->averagePayedPriceNet = $position->getAveragePayedPriceNet();
            $this->breakEvenPrice = $position->getBreakEventPrice();
        } else {
            $this->cashValue = $position->getCashValue();
        }
        $this->investment = $position->getSummedInvestmentGross();
        $this->transactionFeesTotal = $position->getSummedFees();
        $this->collectedDividends = $position->getCollectedDividends();
        $this->collectedDividendsCurrency = $position->getCollectedDividendsCurrency();
        $this->projectedNextDividendPayment = $position->calculateNextDividendPayment();
        $this->projectedNextDividendCurrency = $position->getLastDividendTransactionByDate() ? $position->getLastDividendTransactionByDate()->getCurrency()->getName() : null;
        $this->collectedInterest = $position->getCollectedInterest();
        $this->collectedCoupons = $position->getCollectedCoupons();
        if (false === $position->isActive()) {
            $this->closedResult = $position->getSummedInvestmentGross() * -1;
            // automatic setting active-until
            if (null === $position->getActiveUntil()) {
                $transactions = $position->getTransactions();
                if (count($transactions) > 0) {
                    $lastTransaction = $transactions[count($transactions) -1];
                    if ($lastTransaction->getTitle() == 'Verkauf') {
                        $position->setActiveUntil($lastTransaction->getDate());
                    }
                }
            }
        }
        $this->performance = [];

        $this->lastRate = null;
    }

    /**
     * @param Stockrate|null $lastRate
     */
    public function setLastRate(?Stockrate $lastRate): void
    {
        $this->lastRate = $lastRate;
    }

    /**
     * @param array $performance
     */
    public function setPerformanceData(array $performance): void
    {
        $this->performance = $performance;
    }

}
