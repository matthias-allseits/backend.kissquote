<?php

namespace App\Service;

use App\Entity\Position;
use App\Entity\Stockrate;
use App\Model\Balance;
use App\Repository\StockrateRepository;
use Doctrine\ORM\EntityManagerInterface;


class BalanceService
{

    private $em;
    private $swissquoteService;

    public function __construct(EntityManagerInterface $em, SwissquoteService $swissquoteService)
    {
        $this->em = $em;
        $this->swissquoteService = $swissquoteService;
    }


    public function getBalanceForPosition(Position $position, \DateTime $timeWarpDate = null): Balance
    {
        $balance = new Balance($position);
        if (false === $position->isCash()) {
            $currencyName = $position->getCurrency()->getName();
            $lastRate = null;
            $performance = [];
            if (null !== $position->getShare()) {
                if (null === $timeWarpDate) {
                    /** @var Stockrate $lastRate */
                    $lastRate = $this->em->getRepository(Stockrate::class)->findOneBy(
                        ['isin' => $position->getShare()->getIsin(), 'marketplace' => $position->getShare()->getMarketplace(), 'currencyName' => $currencyName],
                        ['date' => 'DESC']
                    );
                } else {
                    $lastRate = $this->getRateByDate($timeWarpDate, $position);
                }
            }
            if (null !== $lastRate) {
                $balance->setLastRate($lastRate);

                if ($position->isActive()) {
                    $yesterday = new \DateTime();
//                    var_dump($yesterday->format('Y-m-d H:i:s e'));
                    if ((int) $yesterday->format('H') < 10) {
                        $yesterday->sub(new \DateInterval('P2D'));
                    } else {
                        $yesterday->sub(new \DateInterval('P1D'));
                    }
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
                    $fiveYearAgo = new \DateTime();
                    $fiveYearAgo->sub(new \DateInterval('P5Y'));
                    $tenYearAgo = new \DateTime();
                    $tenYearAgo->sub(new \DateInterval('P10Y'));
                    $performanceDates = [$yesterday, $oneWeekAgo, $oneMonthAgo, $threeMonthsAgo, $sixMonthsAgo, $oneYearAgo, $threeYearAgo, $fiveYearAgo, $tenYearAgo];
                    foreach ($performanceDates as $date) {
                        $rate = $this->getRateByDate($date, $position);
//                    $rate = null;
                        if (null !== $rate) {
                            $value = round((100 / $rate->getRate() * $lastRate->getRate()) - 100, 1);
                            $performance[] = $value;
                        }
                    }
                }
            }
            $balance->setPerformanceData($performance);
        }

        return $balance;
    }


    private function getRateByDate(\DateTime $date, Position $position): ?Stockrate
    {
        $rate = null;

        if ($date >= $position->getActiveFrom()) {
            $currencyName = $position->getCurrency()->getName();
            /** @var StockrateRepository $repos */
            $repos = $this->em->getRepository(Stockrate::class);
            $rate = $repos->getLastRateByIsinAndMarketAndCurrencyNameAndDate($position->getShare()->getIsin(), $position->getShare()->getMarketplace(), $currencyName, $date);

            if (null === $rate) {
                if (null !== $position->getShare()) {
                    $rate = $this->swissquoteService->getLastQuoteByDate($date, $position->getShare());
                }
            }
        }

        return $rate;
    }

}
