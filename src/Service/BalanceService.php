<?php

namespace App\Service;


use App\Entity\Position;
use App\Entity\SwissquoteShare;
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
            if ($currencyName == 'CHF') {
                $currencyName = 'SFR';
            } elseif ($currencyName == 'USD') {
                $currencyName = 'DLR';
            }
            $swissquoteShare = $this->em->getRepository(SwissquoteShare::class)->findOneBy(['isin' => $position->getShare()->getIsin(), 'currency' => $currencyName]);
            if (null !== $swissquoteShare) {
                $balance->setLastRate($swissquoteShare->getLastRate());
            } else {
// todo: get quote from swissquote on the fly
            }
        }

        return $balance;
    }

}
