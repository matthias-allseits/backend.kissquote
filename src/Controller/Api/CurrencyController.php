<?php

namespace App\Controller\Api;

use App\Entity\Currency;
use App\Entity\LogEntry;
use App\Entity\Portfolio;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;


class CurrencyController extends BaseController
{

    /**
     * @Rest\Get ("/currency", name="list_currencies")
     * @param Request $request
     * @return View
     */
    public function listCurrencies(Request $request): View
    {
        $portfolio = $this->getPortfolio($request);

        $currencies = $portfolio->getCurrencies();

        return View::create($currencies, Response::HTTP_CREATED);
    }


    /**
     * todo: probably useless?
     * @Rest\Post("/currency", name="create_currency")
     * @param Request $request
     * @return View
     */
    public function createCurrency(Request $request): View
    {
        $portfolio = $this->getPortfolio($request);

        $serializer = SerializerBuilder::create()->build();
        $content = json_decode($request->getContent());
        /** @var Currency $postedCurrency */
        $postedCurrency = $serializer->deserialize(json_encode($content), Currency::class, 'json');

        $existingCurrency = $portfolio->getCurrencyByName($postedCurrency->getName());
        if (null === $existingCurrency) {
            $postedCurrency->setPortfolioId($portfolio->getId());

            $this->getDoctrine()->getManager()->persist($postedCurrency);
            $this->getDoctrine()->getManager()->flush();
        }

        return new View("Currency Creation Successfully", Response::HTTP_OK);
    }


    /**
     * @Rest\Put("/currency/{currencyId}", name="update_currency")
     * @param Request $request
     * @param int $currencyId
     * @return View
     */
    public function updateCurrency(Request $request, int $currencyId): View
    {
        $portfolio = $this->getPortfolio($request);

        $serializer = SerializerBuilder::create()->build();
        $content = json_decode($request->getContent());
//        unset($content->balance);
//        unset($content->transactions);
//        var_dump($content);
        /** @var Currency $puttedCurrency */
        $puttedCurrency = $serializer->deserialize(json_encode($content), Currency::class, 'json');

        $existingCurrency = $portfolio->getCurrencyById($puttedCurrency->getId());

        if (null !== $existingCurrency && $puttedCurrency->getId() == $existingCurrency->getId()) {
            $existingCurrency->setName($puttedCurrency->getName());
            $existingCurrency->setRate($puttedCurrency->getRate());

            $this->getDoctrine()->getManager()->persist($existingCurrency);

            $this->makeLogEntry('update currency', $existingCurrency->getName() . ' new rate: ' . $existingCurrency->getRate());

            $this->getDoctrine()->getManager()->flush();

            return new View("Currency Update Successfully", Response::HTTP_OK);
        } else {

            throw new AccessDeniedException();
        }

    }

}
