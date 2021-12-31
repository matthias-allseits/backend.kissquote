<?php

namespace App\Controller\Api;

use App\Entity\Portfolio;
use App\Entity\Position;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;


class PositionController extends AbstractFOSRestController
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


    /**
     * @Rest\Get ("/position", name="list_positions")
     * @param Request $request
     * @return View
     * @throws \Exception
     */
    public function listPositions(Request $request): View
    {
        $hashKey = $request->headers->get('Authorization');
        $portfolio = $this->getDoctrine()->getRepository(Portfolio::class)->findOneBy(['hashKey' => $hashKey]);
        if (null === $portfolio) {
            throw new \Exception(AuthenticationException::class);
        }

        $positions = $portfolio->getAllPositions();

        return View::create($positions, Response::HTTP_CREATED);
    }

}
