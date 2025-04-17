<?php

namespace App\Controller\Api;

use App\Entity\Position;
use App\Entity\Strategy;
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
     * @return View
     */
    public function listStrategies(Request $request): View
    {
        $portfolio = $this->getPortfolioByAuth($request);

        $strategies = $portfolio->getStrategies();

        return View::create($strategies, Response::HTTP_CREATED);
    }


    /**
     * @Rest\Post("/strategy", name="create_strategy")
     * @param Request $request
     * @return View
     */
    public function createStrategy(Request $request): View
    {
        $portfolio = $this->getPortfolioByAuth($request);

        $serializer = SerializerBuilder::create()->build();
        $content = json_decode($request->getContent());
        /** @var Strategy $postedStrategy */
        $postedStrategy = $serializer->deserialize(json_encode($content), Strategy::class, 'json');

        $existingStrategy = $portfolio->getStrategyByName($postedStrategy->getName());
        if (null === $existingStrategy) {
            $postedStrategy->setPortfolioId($portfolio->getId());

            $this->getDoctrine()->getManager()->persist($postedStrategy);
            $this->getDoctrine()->getManager()->flush();
        }

        return new View("Strategy Creation Successfully", Response::HTTP_OK);
    }


    /**
     * @Rest\Put("/strategy/{strategyId}", name="update_strategy")
     * @param Request $request
     * @param int $strategyId
     * @return View
     */
    public function updateStrategy(Request $request, int $strategyId): View
    {
        $portfolio = $this->getPortfolioByAuth($request);

        $serializer = SerializerBuilder::create()->build();
        $content = json_decode($request->getContent());
        /** @var Strategy $puttedStrategy */
        $puttedStrategy = $serializer->deserialize(json_encode($content), Strategy::class, 'json');

        $existingStrategy = $portfolio->getStrategyById($puttedStrategy->getId());

        if (null !== $existingStrategy && $puttedStrategy->getId() == $existingStrategy->getId()) {
            $existingStrategy->setName($puttedStrategy->getName());

            $this->getDoctrine()->getManager()->persist($existingStrategy);

            $this->makeLogEntry('update strategy', $existingStrategy->getName());

            $this->getDoctrine()->getManager()->flush();

            return new View("Strategy Update Successfully", Response::HTTP_OK);
        } else {

            throw new AccessDeniedException();
        }

    }


    /**
     * @Rest\Delete("/strategy/{strategyId}", name="delete_strategy")
     * @param Request $request
     * @param int $strategyId
     * @return View
     */
    public function deleteStrategy(Request $request, int $strategyId): View
    {
        $portfolio = $this->getPortfolioByAuth($request);

        $strategy = $this->getDoctrine()->getRepository(Strategy::class)->find($strategyId);

        $affectedPositions = $this->getDoctrine()->getRepository(Position::class)->findBy(['strategy' => $strategyId]);
        foreach($affectedPositions as $position) {
            $position->setStrategy(null);
            $this->getDoctrine()->getManager()->persist($position);
            $this->addPositionLogEntry('Entferne gelöschten Sektor: ' . $strategy->getName(), $position);
        }
        $this->getDoctrine()->getManager()->remove($strategy);

        $this->makeLogEntry('delete strategy', $strategy);

        $this->getDoctrine()->getManager()->flush();

        return new View("Strategy Delete Successfully", Response::HTTP_OK);
    }

}
