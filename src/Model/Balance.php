<?php

namespace App\Model;

use App\Entity\Position;
use App\Entity\Stockrate;


class Balance
{

    private float $amount;

    private ?float $firstRate;

    private ?float $averagePayedPriceGross;

    private ?float $averagePayedPriceNet;

    private ?float $breakEvenPrice;

    private int $investment;

    private int $transactionFeesTotal;

    private int $collectedDividends;

    private string $collectedDividendsCurrency;

    private ?int $projectedNextDividendPayment;

    private ?string $projectedNextDividendCurrency;

    private int $collectedInterest;

    private int $collectedCoupons;

    private ?Stockrate $lastRate;

    private float $cashValue;

    private int $closedResult;

    private array $performance = [];


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

    public function getLastRate(): ?Stockrate
    {
        return $this->lastRate;
    }

    public function setLastRate(?Stockrate $lastRate): void
    {
        $this->lastRate = $lastRate;
    }

    public function setPerformanceData(array $performance): void
    {
        $this->performance = $performance;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): void
    {
        $this->amount = $amount;
    }

    public function getFirstRate(): float
    {
        return $this->firstRate;
    }

    public function setFirstRate(float $firstRate): void
    {
        $this->firstRate = $firstRate;
    }

    public function getAveragePayedPriceGross(): float
    {
        return $this->averagePayedPriceGross;
    }

    public function setAveragePayedPriceGross(float $averagePayedPriceGross): void
    {
        $this->averagePayedPriceGross = $averagePayedPriceGross;
    }

    public function getAveragePayedPriceNet(): float
    {
        return $this->averagePayedPriceNet;
    }

    public function setAveragePayedPriceNet(float $averagePayedPriceNet): void
    {
        $this->averagePayedPriceNet = $averagePayedPriceNet;
    }

    public function getBreakEvenPrice(): float
    {
        return $this->breakEvenPrice;
    }

    public function setBreakEvenPrice(float $breakEvenPrice): void
    {
        $this->breakEvenPrice = $breakEvenPrice;
    }

    public function getInvestment(): int
    {
        return $this->investment;
    }

    public function setInvestment(int $investment): void
    {
        $this->investment = $investment;
    }

    public function getTransactionFeesTotal(): int
    {
        return $this->transactionFeesTotal;
    }

    public function setTransactionFeesTotal(int $transactionFeesTotal): void
    {
        $this->transactionFeesTotal = $transactionFeesTotal;
    }

    public function getCollectedDividends(): int
    {
        return $this->collectedDividends;
    }

    public function setCollectedDividends(int $collectedDividends): void
    {
        $this->collectedDividends = $collectedDividends;
    }

    public function getCollectedDividendsCurrency(): string
    {
        return $this->collectedDividendsCurrency;
    }

    public function setCollectedDividendsCurrency(string $collectedDividendsCurrency): void
    {
        $this->collectedDividendsCurrency = $collectedDividendsCurrency;
    }

    public function getProjectedNextDividendPayment(): int
    {
        return $this->projectedNextDividendPayment;
    }

    public function setProjectedNextDividendPayment(int $projectedNextDividendPayment): void
    {
        $this->projectedNextDividendPayment = $projectedNextDividendPayment;
    }

    public function getProjectedNextDividendCurrency(): string
    {
        return $this->projectedNextDividendCurrency;
    }

    public function setProjectedNextDividendCurrency(string $projectedNextDividendCurrency): void
    {
        $this->projectedNextDividendCurrency = $projectedNextDividendCurrency;
    }

    public function getCollectedInterest(): int
    {
        return $this->collectedInterest;
    }

    public function setCollectedInterest(int $collectedInterest): void
    {
        $this->collectedInterest = $collectedInterest;
    }

    public function getCollectedCoupons(): int
    {
        return $this->collectedCoupons;
    }

    public function setCollectedCoupons(int $collectedCoupons): void
    {
        $this->collectedCoupons = $collectedCoupons;
    }

    public function getCashValue(): float
    {
        return $this->cashValue;
    }

    public function setCashValue(float $cashValue): void
    {
        $this->cashValue = $cashValue;
    }

    public function getClosedResult(): int
    {
        return $this->closedResult;
    }

    public function setClosedResult(int $closedResult): void
    {
        $this->closedResult = $closedResult;
    }

    public function getPerformance(): array
    {
        return $this->performance;
    }

    public function setPerformance(array $performance): void
    {
        $this->performance = $performance;
    }

}
