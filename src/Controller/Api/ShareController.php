<?php

namespace App\Controller\Api;

use App\Entity\Share;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class ShareController extends AbstractFOSRestController
{

    // todo: probably useless?
    /**
     * @Rest\Get ("/share", name="list_shares")
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return View
     */
    public function listShares(Request $request, EntityManagerInterface $entityManager): View
    {
        $shares = $entityManager->getRepository(Share::class)->findAll();

        return View::create($shares, Response::HTTP_CREATED);
    }

}
