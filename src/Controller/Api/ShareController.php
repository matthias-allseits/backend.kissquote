<?php

namespace App\Controller\Api;

use App\Entity\Share;
use App\Entity\ShareheadShare;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class ShareController extends AbstractFOSRestController
{

    /**
     * @Rest\Get ("/share", name="list_shares")
     * @param Request $request
     * @return View
     */
    public function listShares(Request $request): View
    {
        $shares = $this->getDoctrine()->getRepository(Share::class)->findAll();

        return View::create($shares, Response::HTTP_CREATED);
    }


    /**
     * @Rest\Get ("/share/swissquote", name="list_swissquote_shares")
     * @param Request $request
     * @return View
     */
    public function listSwissquoteShares(Request $request): View
    {
        $shares = $this->getDoctrine()->getRepository(ShareheadShare::class)->findAll();

        return View::create($shares, Response::HTTP_CREATED);
    }

}
