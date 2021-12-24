<?php

namespace App\Controller\Api;

use App\Entity\Position;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class PosititonController extends AbstractFOSRestController
{

    /**
     * @Rest\Get ("/position/{positionId}", name="get_position")
     * @param Request $request
     * @param int $positionId
     * @return View
     */
    public function getPosition(Request $request, int $positionId): View
    {

        $position = $this->getDoctrine()->getRepository(Position::class)->find($positionId);

        return View::create($position, Response::HTTP_CREATED);
    }

}
