<?php

namespace App\Controller\Api;

use App\Entity\Portfolio;
use App\Entity\Translation;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class PortfolioController extends AbstractFOSRestController
{

    /**
     * @Rest\Post("/portfolio", name="get_translations")
     * @param Request $request
     * @param string $lang
     * @return View
     */
    public function translations(Request $request): View
    {
        $portfolio = new Portfolio();
        $portfolio->setUserName('gustav_88');
        $portfolio->setHashKey('zuzuzuzuzu');
        $portfolio->setStartDate(new \DateTime());

        return View::create($portfolio, Response::HTTP_CREATED);
    }

}
