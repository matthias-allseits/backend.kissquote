<?php

namespace App\Controller\Api;

use App\Entity\Currency;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Serializer\SerializerInterface;


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
     */
//    #[Route('/api/currency', name: 'create_currency', methods: ['POST', 'OPTIONS'])]
    public function createCurrency(Request $request, EntityManagerInterface $entityManager, SerializerInterface $serializer): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

        /** @var Currency $postedCurrency */
        $postedCurrency = $serializer->deserialize($request->getContent(), Currency::class, 'json');

        $existingCurrency = $portfolio->getCurrencyByName($postedCurrency->getName());
        if (null === $existingCurrency) {
            $postedCurrency->setPortfolioId($portfolio->getId());

            $entityManager->persist($postedCurrency);
            $entityManager->flush();
        }

        return new View($postedCurrency, Response::HTTP_OK);
    }


    #[Route('/api/currency/{currencyId}', name: 'update_currency', methods: ['PUT', 'OPTIONS'])]
    public function updateCurrency(Request $request, int $currencyId, EntityManagerInterface $entityManager, SerializerInterface $serializer): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

        /** @var Currency $puttedCurrency */
        $puttedCurrency = $serializer->deserialize($request->getContent(), Currency::class, 'json');

        $existingCurrency = $portfolio->getCurrencyById($currencyId);

        if (null !== $existingCurrency && $existingCurrency->getId() == $currencyId) {
            $existingCurrency->setName($puttedCurrency->getName());
            $existingCurrency->setRate($puttedCurrency->getRate());

            $entityManager->persist($existingCurrency);

            $this->makeLogEntry('update currency', $existingCurrency->getName() . ' new rate: ' . $existingCurrency->getRate(), $entityManager);

            $entityManager->flush();

            return new View($existingCurrency, Response::HTTP_OK);
        } else {

            throw new AccessDeniedException();
        }

    }

}
