<?php

namespace App\Controller\Api;

use App\Entity\Portfolio;
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
    public function createPortfolio(Request $request): View
    {
        // todo: implement missing try catch loop since the randoms will not be unique...
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


    /**
     * @Rest\Get("/portfolio/restore/{key}", name="restore_portfolio")
     * @param Request $request
     * @param string $key
     * @return View
     */
    public function restorePortfolio(Request $request, string $key): View
    {
//        var_dump($_GET);
//        var_dump($_POST);
//        var_dump($request->getContent());
//        $postedKey = $request->request->get('bambus');
//        var_dump($postedKey);

        $postedKey = $request->query->get('key');

        $portfolio = $this->getDoctrine()->getRepository(Portfolio::class)->findOneBy(['hashKey' => $key]);

        return View::create($portfolio, Response::HTTP_CREATED);
    }

}
