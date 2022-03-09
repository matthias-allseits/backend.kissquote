<?php

namespace App\Controller\Api;

use App\Entity\Marketplace;
use App\Entity\Portfolio;
use App\Entity\Position;
use App\Service\BalanceService;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;


class PositionController extends AbstractFOSRestController
{

    /**
     * @Rest\Get ("/position/{positionId}", name="get_position")
     * @param Request $request
     * @param int $positionId
     * @return View
     */
    public function getPosition(Request $request, int $positionId, BalanceService $balanceService): View
    {

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
        $hashKey = $request->headers->get('Authorization');
        $portfolio = $this->getDoctrine()->getRepository(Portfolio::class)->findOneBy(['hashKey' => $hashKey]);
        if (null === $portfolio) {
            throw new AccessDeniedException();
        }

        $positions = $portfolio->getAllPositions();

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
        // todo: this has to be moved to the security implementation
        $key = $request->headers->get('Authorization');
        $portfolio = $this->getDoctrine()->getRepository(Portfolio::class)->findOneBy(['hashKey' => $key]);
        if (null === $portfolio) {
            throw new AccessDeniedException();
        }

        $serializer = SerializerBuilder::create()->build();
        /** @var Position $position */
        $position = $serializer->deserialize($request->getContent(), Position::class, 'json');

        $bankAccount = $portfolio->getBankAccountById($position->getBankAccount()->getId());
        if (null === $bankAccount) {
            throw new AccessDeniedException();
        } else {
            $position->setBankAccount($bankAccount);
        }

        // todo: get share by isin is not enough. currency is missing
        $share = $portfolio->getShareByIsin($position->getShare()->getIsin());
        if (null === $share) {
            $share = $position->getShare();
            if (strlen($share->getShortname()) == 0) {
                $share->setShortname(substr($share->getName(), 0, 15));
            }
            $marketplace = $this->getDoctrine()->getRepository(Marketplace::class)->find($share->getMarketplace()->getId());
            $share->setMarketplace($marketplace);
            $share->setPortfolio($portfolio);
            $share->setType('stock');
            $this->getDoctrine()->getManager()->persist($share);
        }
        $position->setShare($share);

        $currency = $portfolio->getCurrencyByName($position->getCurrency()->getName());
        if (null === $currency) {
            $currency = $position->getCurrency();
            $currency->setPortfolio($portfolio);
            $this->getDoctrine()->getManager()->persist($currency);
        }
        $position->setCurrency($currency);
        $position->getShare()->setCurrency($currency);

        // happens in case of a import
        if (count($position->getTransactions()) > 0) {
            $this->getDoctrine()->getManager()->persist($position);
            $persistedTransactions = [];
            foreach($position->getTransactions() as $transaction) {
                $transaction->setQuantity(abs($transaction->getQuantity()));
                $transaction->setPosition($position);
                $this->getDoctrine()->getManager()->persist($transaction);
                $persistedTransactions[] = $transaction;
            }
            $position->setTransactions($persistedTransactions);
        }

        $this->getDoctrine()->getManager()->persist($position);
        $this->getDoctrine()->getManager()->flush();

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
        $key = $request->headers->get('Authorization');
        $portfolio = $this->getDoctrine()->getRepository(Portfolio::class)->findOneBy(['hashKey' => $key]);
        if (null === $portfolio) {
            throw new AccessDeniedException();
        }

        $serializer = SerializerBuilder::create()->build();
        /** @var Position $position */
        $position = $serializer->deserialize($request->getContent(), Position::class, 'json');

        $bankAccount = $portfolio->getBankAccountById($position->getBankAccount()->getId());
        if (null === $bankAccount) {
            throw new AccessDeniedException();
        } else {
            $position->setBankAccount($bankAccount);
        }

        $currency = $portfolio->getCurrencyByName($position->getCurrency()->getName());
        if (null === $currency) {
            $currency = $position->getCurrency();
            $currency->setPortfolio($portfolio);
            $this->getDoctrine()->getManager()->persist($currency);
        }
        $position->setCurrency($currency);

        // happens in case of a import
        if ($position->getShare()->getId() == 0) {
            $position->setShare(null);
        }

        // happens in case of a import
        if (count($position->getTransactions()) > 0) {
            $this->getDoctrine()->getManager()->persist($position);
            $persistedTransactions = [];
            foreach($position->getTransactions() as $transaction) {
                $transaction->setQuantity(abs($transaction->getQuantity()));
                $transaction->setPosition($position);
                $this->getDoctrine()->getManager()->persist($transaction);
                $persistedTransactions[] = $transaction;
            }
            $position->setTransactions($persistedTransactions);
        }

        $this->getDoctrine()->getManager()->persist($position);
        $this->getDoctrine()->getManager()->flush();

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
        $key = $request->headers->get('Authorization');
        $portfolio = $this->getDoctrine()->getRepository(Portfolio::class)->findOneBy(['hashKey' => $key]);
        if (null === $portfolio) {
            throw new AccessDeniedException();
        }

        $serializer = SerializerBuilder::create()->build();
        $content = json_decode($request->getContent());
        unset($content->balance);
        unset($content->transactions);
//        var_dump($content);
        /** @var Position $newPosition */
        $newPosition = $serializer->deserialize(json_encode($content), Position::class, 'json');

        $oldPosition = $this->getDoctrine()->getRepository(Position::class)->find($newPosition->getId());
        if (null !== $oldPosition) {
            $oldPosition->setDividendPeriodicity($newPosition->getDividendPeriodicity());
            $oldPosition->getShare()->setName($newPosition->getShare()->getName());
            $oldPosition->getShare()->setIsin($newPosition->getShare()->getIsin());

            $marketplace = $this->getDoctrine()->getRepository(Marketplace::class)->find($newPosition->getShare()->getMarketplace()->getId());
            $oldPosition->getShare()->setMarketplace($marketplace);

            $currency = $portfolio->getCurrencyByName($newPosition->getCurrency()->getName());
            if (null === $currency) {
                $currency = $newPosition->getCurrency();
                $currency->setPortfolio($portfolio);
                $this->getDoctrine()->getManager()->persist($currency);
            }
            $oldPosition->setCurrency($currency);
            $oldPosition->getShare()->setCurrency($currency);

            $this->getDoctrine()->getManager()->persist($oldPosition);
            $this->getDoctrine()->getManager()->flush();
        }

        return new View("Position Update Successfully", Response::HTTP_OK);
    }


    /**
     * @Rest\Delete("/position/{positionId}", name="delete_position")
     * @param Request $request
     * @param int $positionId
     * @return View
     */
    public function deletePosition(Request $request, int $positionId): View
    {
        // todo: security check!!!
        $position = $this->getDoctrine()->getRepository(Position::class)->find($positionId);
        $this->getDoctrine()->getManager()->remove($position);
        $this->getDoctrine()->getManager()->flush();

        return new View("Position Delete Successfully", Response::HTTP_OK);
    }

}
