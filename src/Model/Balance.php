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


    public function __construct(Position $position)
    {
        $this->amount = $position->getCountOfSharesByDate();
        $this->averagePayedPriceGross = $position->getAveragePayedPriceGross();
        $this->averagePayedPriceNet = $position->getAveragePayedPriceNet();
        $this->investment = $position->getSummedInvestmentGross();
        $this->transactionFeesTotal = $position->getSummedFees();
    }

}
