<?php

namespace App\Controller\Api;

use App\Entity\Marketplace;
use App\Entity\Share;
use App\Entity\ShareheadShare;
use App\Entity\Stockrate;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class StockRateController extends AbstractFOSRestController
{

    /**
     * @Rest\Get ("/stockrate/{isin}/{marketplace}/{currency}/{dateStamp}", name="get_share_stockrate")
     * @param Request $request
     * @return View
     */
    public function getStockRate(Request $request, string $isin, string $marketplace, string $currency, string $dateStamp): View
    {
        $marketplace = $this->getDoctrine()->getRepository(Marketplace::class)->findOneBy(['urlKey' => $marketplace]);
        $date = new \DateTime($dateStamp);
        $stockRate = $this->getDoctrine()->getRepository(Stockrate::class)->findOneBy(['isin' => $isin, 'marketplace' => $marketplace, 'currencyName' => $currency, 'date' => $date]);

        return View::create($stockRate, Response::HTTP_CREATED);
    }

}
