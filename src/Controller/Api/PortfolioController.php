<?php

namespace App\Controller\Api;

use App\Entity\Portfolio;
use App\Entity\Translation;
use App\Helper\RandomizeHelper;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class PortfolioController extends AbstractFOSRestController
{

    /**
     * @Rest\Post("/portfolio", name="create_portfolio")
     * @param Request $request
     * @return View
     */
    public function translations(Request $request): View
    {
        $randomUserName = RandomizeHelper::getRandomUserName();
        $randomHashKey = RandomizeHelper::getRandomHashKey();

        $portfolio = new Portfolio();
        $portfolio->setUserName($randomUserName);
        $portfolio->setHashKey($randomHashKey);
        $portfolio->setStartDate(new \DateTime());

        $this->getDoctrine()->getManager()->persist($portfolio);
        $this->getDoctrine()->getManager()->flush();

        return View::create($portfolio, Response::HTTP_CREATED);
    }

}
