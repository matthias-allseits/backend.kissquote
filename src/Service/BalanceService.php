<?php

namespace App\Service;


use App\Entity\Position;
use App\Entity\UsersShareStockrate;
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
            /** @var UsersShareStockrate $lastRate */
            $lastRate = $this->em->getRepository(UsersShareStockrate::class)->findOneBy(
                ['isin' => $position->getShare()->getIsin(), 'marketplace' => $position->getShare()->getMarketplace(), 'currencyName' => $currencyName],
                ['date' => 'DESC']
            );
            if (null !== $lastRate) {
                $balance->setLastRate($lastRate);
            } else {
                // todo: get quote from swissquote on the fly
            }
        }

        return $balance;
    }

}
