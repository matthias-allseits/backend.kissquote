<?php

namespace App\Controller\Api;

use App\Entity\Currency;
use App\Entity\Marketplace;
use App\Entity\Portfolio;
use App\Entity\Position;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;


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


    /**
     * @Rest\Put("/currency/{currencyId}", name="update_currency")
     * @param Request $request
     * @param int $currencyId
     * @return View
     */
    public function updatePosition(Request $request, int $currencyId): View
    {
        $key = $request->headers->get('Authorization');
        $portfolio = $this->getDoctrine()->getRepository(Portfolio::class)->findOneBy(['hashKey' => $key]);
        if (null === $portfolio) {
            throw new AccessDeniedException();
        }

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
            $this->getDoctrine()->getManager()->flush();

            return new View("Currency Update Successfully", Response::HTTP_OK);
        } else {

            throw new AccessDeniedException();
        }

    }

}
