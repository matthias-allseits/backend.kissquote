<?php

namespace App\Controller\Api;

use App\Entity\Marketplace;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class MarketplaceController extends AbstractFOSRestController
{

    /**
     * @Rest\Get ("/marketplace", name="list_marketplaces")
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return View
     */
    public function listMarketplaces(Request $request, EntityManagerInterface $entityManager): View
    {
        $marketplaces = $entityManager->getRepository(Marketplace::class)->findAll();

        return View::create($marketplaces, Response::HTTP_CREATED);
    }

}
