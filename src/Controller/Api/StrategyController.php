<?php

namespace App\Controller\Api;

use App\Entity\Position;
use App\Entity\Strategy;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Serializer\SerializerInterface;


class StrategyController extends BaseController
{

    #[Route('/api/strategy', name: 'list_strategies', methods: ['GET', 'OPTIONS'])]
    public function listStrategies(Request $request, EntityManagerInterface $entityManager): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

        $strategies = $portfolio->getStrategies();

        return View::create($strategies, Response::HTTP_CREATED);
    }


    #[Route('/api/strategy', name: 'create_strategy', methods: ['POST', 'OPTIONS'])]
    public function createStrategy(Request $request, EntityManagerInterface $entityManager, SerializerInterface $serializer): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

        /** @var Strategy $postedStrategy */
        $postedStrategy = $serializer->deserialize($request->getContent(), Strategy::class, 'json');

        $existingStrategy = $portfolio->getStrategyByName($postedStrategy->getName());
        if (null === $existingStrategy) {
            $postedStrategy->setPortfolioId($portfolio->getId());

            $entityManager->persist($postedStrategy);
            $entityManager->flush();
        }

        return new View($postedStrategy, Response::HTTP_OK);
    }


    #[Route('/api/strategy/{strategyId}', name: 'update_strategy', methods: ['PUT', 'OPTIONS'])]
    public function updateStrategy(Request $request, int $strategyId, EntityManagerInterface $entityManager, SerializerInterface $serializer): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

        /** @var Strategy $puttedStrategy */
        $puttedStrategy = $serializer->deserialize($request->getContent(), Strategy::class, 'json');

        $existingStrategy = $portfolio->getStrategyById($strategyId);

        if (null !== $existingStrategy && $existingStrategy->getId() == $strategyId) {
            $existingStrategy->setName($puttedStrategy->getName());

            $entityManager->persist($existingStrategy);

            $this->makeLogEntry('update strategy', $existingStrategy->getName(), $entityManager);

            $entityManager->flush();

            return new View($existingStrategy, Response::HTTP_OK);
        } else {

//            return new View($puttedStrategy, Response::HTTP_OK);
            throw new AccessDeniedException();
        }

    }


    #[Route('/api/strategy/{strategyId}', name: 'delete_strategy', methods: ['DELETE', 'OPTIONS'])]
    public function deleteStrategy(Request $request, int $strategyId, EntityManagerInterface $entityManager, SerializerInterface $serializer): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

        $strategy = $entityManager->getRepository(Strategy::class)->find($strategyId);

        $affectedPositions = $entityManager->getRepository(Position::class)->findBy(['strategy' => $strategyId]);
        foreach($affectedPositions as $position) {
            $position->setStrategy(null);
            $entityManager->persist($position);
            $this->addPositionLogEntry('Entferne gelöschten Sektor: ' . $strategy->getName(), $position, $entityManager);
        }
        $entityManager->remove($strategy);

        $this->makeLogEntry('delete strategy', $strategy, $entityManager);

        $entityManager->flush();

        return new View("Strategy Delete Successfully", Response::HTTP_OK);
    }

}
