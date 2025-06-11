<?php

namespace App\Controller\Api;

use App\Entity\Currency;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\View\View;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;


class CurrencyController extends BaseController
{

    #[Route('/api/currency', name: 'list_currencies', methods: ['GET', 'OPTIONS'])]
    public function listCurrencies(Request $request, EntityManagerInterface $entityManager): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

        $currencies = $portfolio->getCurrencies();

        return View::create($currencies, Response::HTTP_CREATED);
    }


    /**
     * todo: probably useless?
     * @Rest\Post("/currency", name="create_currency")
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return View
     */
    public function createCurrency(Request $request, EntityManagerInterface $entityManager): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

        $serializer = SerializerBuilder::create()->build();
        $content = json_decode($request->getContent());
        /** @var Currency $postedCurrency */
        $postedCurrency = $serializer->deserialize(json_encode($content), Currency::class, 'json');

        $existingCurrency = $portfolio->getCurrencyByName($postedCurrency->getName());
        if (null === $existingCurrency) {
            $postedCurrency->setPortfolioId($portfolio->getId());

            $entityManager->persist($postedCurrency);
            $entityManager->flush();
        }

        return new View("Currency Creation Successfully", Response::HTTP_OK);
    }


    /**
     * @Rest\Put("/currency/{currencyId}", name="update_currency")
     * @param Request $request
     * @param int $currencyId
     * @param EntityManagerInterface $entityManager
     * @return View
     */
    public function updateCurrency(Request $request, int $currencyId, EntityManagerInterface $entityManager): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

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

            $entityManager->persist($existingCurrency);

            $this->makeLogEntry('update currency', $existingCurrency->getName() . ' new rate: ' . $existingCurrency->getRate(), $entityManager);

            $entityManager->flush();

            return new View("Currency Update Successfully", Response::HTTP_OK);
        } else {

            throw new AccessDeniedException();
        }

    }

}
