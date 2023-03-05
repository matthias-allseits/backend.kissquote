<?php

namespace App\Controller\Api;

use App\Entity\LogEntry;
use App\Entity\Marketplace;
use App\Entity\Portfolio;
use App\Entity\Position;
use App\Service\BalanceService;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;


class PositionController extends BaseController
{

    /**
     * @Rest\Get ("/position/{positionId}", name="get_position")
     * @param Request $request
     * @param int $positionId
     * @param BalanceService $balanceService
     * @return View
     */
    public function getPosition(Request $request, int $positionId, BalanceService $balanceService): View
    {
        $portfolio = $this->getPortfolio($request);

        $position = $this->getDoctrine()->getRepository(Position::class)->find($positionId);
        $position->setBankAccount(null);

        if (count($position->getTransactions()) > 0) {
            $balance = $balanceService->getBalanceForPosition($position);
            $position->setBalance($balance);
        }

        return View::create($position, Response::HTTP_CREATED);
    }


    /**
     * @Rest\Get ("/position", name="list_positions")
     * @param Request $request
     * @return View
     * @throws \Exception
     */
    public function listPositions(Request $request): View
    {
        $portfolio = $this->getPortfolio($request);

        $positions = $portfolio->getAllPositions();
        foreach($positions as $position) {
            $position->setBankAccount(null);
            $position->setShare(null);
            $position->setCurrency(null);
        }

        return View::create($positions, Response::HTTP_CREATED);
    }


    /**
     * @Rest\Post("/position/bunch", name="create_position_bunch")
     * @param Request $request
     * @return View
     * @throws \Exception
     */
    public function persistPositionsBunch(Request $request): View
    {
        $portfolio = $this->getPortfolio($request);

        $serializer = SerializerBuilder::create()->build();
        $rawPositions = json_decode($request->getContent());
        $bankAccount = null;
        /** @var Position[] $positions */
        $positions = [];
        foreach($rawPositions as $rawPosition) {
            $position = $serializer->deserialize(json_encode($rawPosition), Position::class, 'json');
            if (null !== $position->getBankAccount()) {
                $portfolio = $this->getPortfolio($request); // do it again
                $bankAccount = $portfolio->getBankAccountById($position->getBankAccount()->getId());
                if (null === $bankAccount) {
                    throw new AccessDeniedException();
                }
                $position->setBankAccount($bankAccount);

                $this->persistShare($portfolio, $position);
                $this->persistCurrency($portfolio, $position, $position);

                // happens in case of a import
                if (count($position->getTransactions()) > 0) {
                    $this->persistTransactions($position, $portfolio);
                }
                $this->getDoctrine()->getManager()->persist($position);
                $this->getDoctrine()->getManager()->flush();
            }
        }

        $this->makeLogEntry('persist a bunch of positions', 'bunch-persisting: there will be more than one');

        $this->getDoctrine()->getManager()->flush();

        return View::create($positions, Response::HTTP_CREATED);
    }


    /**
     * @Rest\Post("/position", name="create_position")
     * @param Request $request
     * @return View
     * @throws \Exception
     */
    public function createPosition(Request $request): View
    {
        $portfolio = $this->getPortfolio($request);

        $position = $this->deserializePosition($request, $portfolio);
        $this->persistShare($portfolio, $position);
        $this->persistCurrency($portfolio, $position, $position);

        // happens in case of a import
        // todo: probably useless?
        if (count($position->getTransactions()) > 0) {
            $this->persistTransactions($position, $portfolio);
        }

        $this->getDoctrine()->getManager()->persist($position);

        $this->makeLogEntry('create new position', $position);

        $this->getDoctrine()->getManager()->flush();

        $position->setBankAccount(null);
        return View::create($position, Response::HTTP_OK);
    }


    /**
     * @Rest\Post("/position/cash", name="create_cash_position")
     * @param Request $request
     * @return View
     * @throws \Exception
     */
    public function createCashPosition(Request $request): View
    {
        $portfolio = $this->getPortfolio($request);

        $position = $this->deserializePosition($request, $portfolio);
        $this->persistCurrency($portfolio, $position, $position);

        // happens in case of a import
        if ($position->getShare()->getId() == 0) {
            $position->setShare(null);
        }

        // happens in case of a import
        // todo: probably useless?
        if (count($position->getTransactions()) > 0) {
            $this->persistTransactions($position, $portfolio);
        }

        $this->getDoctrine()->getManager()->persist($position);
        $this->getDoctrine()->getManager()->flush();

        $position->setBankAccount(null);
        return View::create($position, Response::HTTP_OK);
    }


    /**
     * @Rest\Put("/position/{positionId}", name="update_position")
     * @param Request $request
     * @param int $positionId
     * @return View
     * @throws \Exception
     */
    public function updatePosition(Request $request, int $positionId): View
    {
        $portfolio = $this->getPortfolio($request);

        $serializer = SerializerBuilder::create()->build();
        $content = json_decode($request->getContent());
        unset($content->balance);
        unset($content->transactions);
//        var_dump($content);
        /** @var Position $newPosition */
        $newPosition = $serializer->deserialize(json_encode($content), Position::class, 'json');

        /** @var Position $oldPosition */
        $oldPosition = $this->getDoctrine()->getRepository(Position::class)->find($newPosition->getId());
        if (null !== $oldPosition) {
            $oldPosition->setDividendPeriodicity($newPosition->getDividendPeriodicity());
            $oldPosition->setActiveFrom($newPosition->getActiveFrom());
            $oldPosition->setActiveUntil($newPosition->getActiveUntil());
            $oldPosition->setActive($newPosition->isActive());
            $oldPosition->setShareheadId($newPosition->getShareheadId());
            $oldPosition->getShare()->setName($newPosition->getShare()->getName());
            $oldPosition->getShare()->setIsin($newPosition->getShare()->getIsin());

            $marketplace = $this->getDoctrine()->getRepository(Marketplace::class)->find($newPosition->getShare()->getMarketplace()->getId());
            $oldPosition->getShare()->setMarketplace($marketplace);

            $this->persistCurrency($portfolio, $newPosition, $oldPosition);

            $this->getDoctrine()->getManager()->persist($oldPosition);

            $this->makeLogEntry('update position', $oldPosition);

            $this->getDoctrine()->getManager()->flush();
        }

        $oldPosition->setBankAccount(null);
        return new View($oldPosition, Response::HTTP_OK);
    }


    /**
     * @Rest\Delete("/position/{positionId}", name="delete_position")
     * @param Request $request
     * @param int $positionId
     * @return View
     */
    public function deletePosition(Request $request, int $positionId): View
    {
        $portfolio = $this->getPortfolio($request);

        $position = $this->getDoctrine()->getRepository(Position::class)->find($positionId);
        $this->getDoctrine()->getManager()->remove($position);

        $this->makeLogEntry('delete position', $position);

        $this->getDoctrine()->getManager()->flush();

        return new View("Position Delete Successfully", Response::HTTP_OK);
    }


    /**
     * @param Request $request
     * @param $portfolio
     * @return Position
     */
    private function deserializePosition(Request $request, $portfolio): Position
    {
        $serializer = SerializerBuilder::create()->build();
        /** @var Position $position */
        $position = $serializer->deserialize($request->getContent(), Position::class, 'json');

        $bankAccount = $portfolio->getBankAccountById($position->getBankAccount()->getId());
        if (null === $bankAccount) {
            throw new AccessDeniedException();
        } else {
            $position->setBankAccount($bankAccount);
        }

        return $position;
    }


    /**
     * @param Position $position
     * @param Portfolio $portfolio
     */
    private function persistTransactions(Position $position, Portfolio $portfolio): void
    {
        $this->getDoctrine()->getManager()->persist($position);
        $persistedTransactions = [];
        foreach ($position->getTransactions() as $transaction) {
            $transactionCurrency = $portfolio->getCurrencyByName($transaction->getCurrency()->getName());
            if (null === $transactionCurrency) {
                $transactionCurrency = $transaction->getCurrency();
                $transactionCurrency->setPortfolioId($portfolio->getId());
                $this->getDoctrine()->getManager()->persist($transactionCurrency);
            }
            $transaction->setCurrency($transactionCurrency);

            $transaction->setQuantity(abs($transaction->getQuantity()));
            $transaction->setPosition($position);
            $this->getDoctrine()->getManager()->persist($transaction);
            $persistedTransactions[] = $transaction;
        }
        $position->setTransactions($persistedTransactions);
    }


    /**
     * @param Portfolio $portfolio
     * @param Position $position
     */
    private function persistShare(Portfolio $portfolio, Position $position): void
    {
        // todo: get share by isin is not enough. currency is missing
        $share = $portfolio->getShareByIsin($position->getShare()->getIsin());
        if (null === $share) {
            $share = $position->getShare();
            if (strlen($share->getShortname()) == 0) {
                $share->setShortname(substr($share->getName(), 0, 15));
            }
            $marketplace = $this->getDoctrine()->getRepository(Marketplace::class)->find($share->getMarketplace()->getId());
            $share->setMarketplace($marketplace);
            $share->setPortfolioId($portfolio->getId());
            $share->setType('stock');
            $this->getDoctrine()->getManager()->persist($share);
        }
        $position->setShare($share);
    }


    /**
     * @param Portfolio $portfolio
     * @param Position $sourcePosition
     * @param Position $targetPosition
     */
    private function persistCurrency(Portfolio $portfolio, Position $sourcePosition, Position $targetPosition): void
    {
        $currency = $portfolio->getCurrencyByName($sourcePosition->getCurrency()->getName());
        if (null === $currency) {
            $currency = $sourcePosition->getCurrency();
            $currency->setPortfolioId($portfolio->getId());
            $this->getDoctrine()->getManager()->persist($currency);
        }
        $targetPosition->setCurrency($currency);
        if (null !== $targetPosition->getShare()) {
            $targetPosition->getShare()->setCurrency($currency);
        }
    }

}
