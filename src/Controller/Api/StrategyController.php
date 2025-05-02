<?php

namespace App\Controller\Api;

use App\Entity\Position;
use App\Entity\Strategy;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;


class StrategyController extends BaseController
{

    /**
     * @Rest\Get ("/strategy", name="list_strategies")
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return View
     */
    public function listStrategies(Request $request, EntityManagerInterface $entityManager): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

        $strategies = $portfolio->getStrategies();

        return View::create($strategies, Response::HTTP_CREATED);
    }


    /**
     * @Rest\Post("/strategy", name="create_strategy")
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return View
     */
    public function createStrategy(Request $request, EntityManagerInterface $entityManager): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

        $serializer = SerializerBuilder::create()->build();
        $content = json_decode($request->getContent());
        /** @var Strategy $postedStrategy */
        $postedStrategy = $serializer->deserialize(json_encode($content), Strategy::class, 'json');

        $existingStrategy = $portfolio->getStrategyByName($postedStrategy->getName());
        if (null === $existingStrategy) {
            $postedStrategy->setPortfolioId($portfolio->getId());

            $entityManager->persist($postedStrategy);
            $entityManager->flush();
        }

        return new View("Strategy Creation Successfully", Response::HTTP_OK);
    }


    /**
     * @Rest\Put("/strategy/{strategyId}", name="update_strategy")
     * @param Request $request
     * @param int $strategyId
     * @param EntityManagerInterface $entityManager
     * @return View
     */
    public function updateStrategy(Request $request, int $strategyId, EntityManagerInterface $entityManager): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

        $serializer = SerializerBuilder::create()->build();
        $content = json_decode($request->getContent());
        /** @var Strategy $puttedStrategy */
        $puttedStrategy = $serializer->deserialize(json_encode($content), Strategy::class, 'json');

        $existingStrategy = $portfolio->getStrategyById($puttedStrategy->getId());

        if (null !== $existingStrategy && $puttedStrategy->getId() == $existingStrategy->getId()) {
            $existingStrategy->setName($puttedStrategy->getName());

            $entityManager->persist($existingStrategy);

            $this->makeLogEntry('update strategy', $existingStrategy->getName(), $entityManager);

            $entityManager->flush();

            return new View("Strategy Update Successfully", Response::HTTP_OK);
        } else {

            throw new AccessDeniedException();
        }

    }


    /**
     * @Rest\Delete("/strategy/{strategyId}", name="delete_strategy")
     * @param Request $request
     * @param int $strategyId
     * @param EntityManagerInterface $entityManager
     * @return View
     */
    public function deleteStrategy(Request $request, int $strategyId, EntityManagerInterface $entityManager): View
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
