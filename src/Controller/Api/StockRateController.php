<?php

namespace App\Controller\Api;

use App\Entity\Marketplace;
use App\Entity\Stockrate;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class StockRateController extends AbstractFOSRestController
{

    // todo: probably useless?
    // I guess yes
    /**
     * @Rest\Get ("/stockrate/{isin}/{marketplace}/{currency}/{dateStamp}", name="get_share_stockrate")
     * @param Request $request
     * @param string $isin
     * @param string $marketplace
     * @param string $currency
     * @param string $dateStamp
     * @param EntityManagerInterface $entityManager
     * @return View
     * @throws \Exception
     */
//    #[Route('/api/stockrate/{isin}/{marketplace}/{currency}/{dateStamp}', name: 'get_share_stockrate', methods: ['GET', 'OPTIONS'])]
    public function getStockRate(Request $request, string $isin, string $marketplace, string $currency, string $dateStamp, EntityManagerInterface $entityManager): View
    {
        $marketplace = $entityManager->getRepository(Marketplace::class)->findOneBy(['urlKey' => $marketplace]);
        $date = new \DateTime($dateStamp);
        $stockRate = $entityManager->getRepository(Stockrate::class)->findOneBy(['isin' => $isin, 'marketplace' => $marketplace, 'currencyName' => $currency, 'date' => $date]);

        return View::create($stockRate, Response::HTTP_CREATED);
    }

}
