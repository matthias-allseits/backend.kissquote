<?php

namespace App\Controller\Api;

use App\Entity\Currency;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class CurrencyController extends AbstractFOSRestController
{

    /**
     * @Rest\Get ("/currency", name="list_currencies")
     * @param Request $request
     * @return View
     */
    public function listCurrencies(Request $request): View
    {
        $currencies = $this->getDoctrine()->getRepository(Currency::class)->findAll();

        return View::create($currencies, Response::HTTP_CREATED);
    }

}
