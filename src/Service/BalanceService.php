<?php

namespace App\Service;


use App\Entity\Position;
use App\Entity\Share;
use App\Entity\Stockrate;
use App\Model\Balance;
use Doctrine\ORM\EntityManagerInterface;

class BalanceService
{

    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }


    public function getBalanceForPosition(Position $position): Balance
    {
        $balance = new Balance($position);
        if (false === $position->isCash()) {
            $currencyName = $position->getCurrency()->getName();
            $lastRate = null;
            $performance = [1.1, 2.2, 3.3, 4.4, 5.5, 6.6];
            if (null !== $position->getShare()) {
                /** @var Stockrate $lastRate */
                $lastRate = $this->em->getRepository(Stockrate::class)->findOneBy(
                    ['isin' => $position->getShare()->getIsin(), 'marketplace' => $position->getShare()->getMarketplace(), 'currencyName' => $currencyName],
                    ['date' => 'DESC']
                );
            }
            if (null !== $lastRate) {
                $balance->setLastRate($lastRate);

                $performance = [];
                $yesterday = new \DateTime();
                $yesterday->sub(new \DateInterval('P1D'));
                $oneWeekAgo = new \DateTime();
                $oneWeekAgo->sub(new \DateInterval('P7D'));
                $oneMonthAgo = new \DateTime();
                $oneMonthAgo->sub(new \DateInterval('P1M'));
                $threeMonthsAgo = new \DateTime();
                $threeMonthsAgo->sub(new \DateInterval('P3M'));
                $sixMonthsAgo = new \DateTime();
                $sixMonthsAgo->sub(new \DateInterval('P6M'));
                $oneYearAgo = new \DateTime();
                $oneYearAgo->sub(new \DateInterval('P1Y'));
                $threeYearAgo = new \DateTime();
                $threeYearAgo->sub(new \DateInterval('P3Y'));
                $performanceDates = [$yesterday, $oneWeekAgo, $oneMonthAgo, $threeMonthsAgo, $sixMonthsAgo, $oneYearAgo, $threeYearAgo];
                foreach($performanceDates as $date) {
                    $rate = $this->getRateByDate($date, $position);
                    if (null !== $rate) {
                        $value = round((100 / $rate->getRate() * $lastRate->getRate()) - 100, 1);
                        $performance[] = $value;
                    }
                }
            } else {
                // todo: get quote from swissquote on the fly
            }
            $balance->setPerformanceData($performance);
        }

        return $balance;
    }


    private function getRateByDate(\DateTime $date, Position $position): ?Stockrate
    {
        $currencyName = $position->getCurrency()->getName();
        /** @var Stockrate $rate */
        $allRates = $this->em->getRepository(Stockrate::class)->findBy(
            ['isin' => $position->getShare()->getIsin(), 'marketplace' => $position->getShare()->getMarketplace(), 'currencyName' => $currencyName],
            ['date' => 'DESC']
        );
        $hit = null;
        foreach($allRates as $rate) {
            if ($rate->getDate() <= $date) {
                $hit = $rate;
                break;
            }
        }

        return $hit;
    }

}
