<?php

namespace App\Controller\Api;

use App\Entity\Marketplace;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class MarketplaceController extends AbstractFOSRestController
{

    #[Route('/api/marketplace', name: 'list_marketplaces', methods: ['GET', 'OPTIONS'])]
    public function listMarketplaces(Request $request, EntityManagerInterface $entityManager): View
    {
        $marketplaces = $entityManager->getRepository(Marketplace::class)->findAll();

        return View::create($marketplaces, Response::HTTP_CREATED);
    }

}
