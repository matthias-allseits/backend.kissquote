<?php

namespace App\Controller\Api;

use App\Entity\Label;
use App\Entity\LogEntry;
use App\Entity\Marketplace;
use App\Entity\Portfolio;
use App\Entity\Position;
use App\Entity\Sector;
use App\Entity\Strategy;
use App\Service\BalanceService;
use Doctrine\Common\Collections\ArrayCollection;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;


class PositionController extends BaseController
{

    /**
     * @Rest\Get ("/position/{positionId}", name="get_position", requirements={"positionId"="\d+"})
     * @param Request $request
     * @param int $positionId
     * @param BalanceService $balanceService
     * @return View
     */
    public function getPosition(Request $request, int $positionId, BalanceService $balanceService): View
    {
        $portfolio = $this->getPortfolioByAuth($request);

        $position = $this->getDoctrine()->getRepository(Position::class)->find($positionId);
        if ($position->getBankAccount()) {
            $position->setBankAccountName($position->getBankAccount()->getName());
        }
        $position->setBankAccount(null);

        $motherPosition = $this->getDoctrine()->getRepository(Position::class)->findOneBy(['underlying' => $position]);
        if (null !== $motherPosition) {
            $position->setMotherId($motherPosition->getId());
        }

        $balance = $balanceService->getBalanceForPosition($position);
        $position->setBalance($balance);
        $position->setLogEntries(new ArrayCollection(array_reverse($position->getLogEntries()->toArray())));

        if (null !== $position->getUnderlying()) {
            $balance = $balanceService->getBalanceForPosition($position->getUnderlying());
            $position->getUnderlying()->setBalance($balance);
            $position->getUnderlying()->setLogEntries(new ArrayCollection(array_reverse($position->getLogEntries()->toArray())));
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
        $portfolio = $this->getPortfolioByAuth($request);

        $positions = $portfolio->getAllPositions();
        foreach($positions as $position) {
            $position->setBankAccount(null);
            $position->setShare(null);
            $position->setCurrency(null);
        }

        return View::create($positions, Response::HTTP_CREATED);
    }


    /**
     * @Rest\Get ("/position/active", name="list_active_positions")
     * @param Request $request
     * @return View
     * @throws \Exception
     */
    public function listActivePositions(Request $request): View
    {
        $portfolio = $this->getPortfolioByAuth($request);

        $positions = $portfolio->getAllPositions();
        $activePositions = [];
        foreach($positions as $position) {
            if ($position->isActive()) {
                $position->setBankAccount(null);
//                $position->setShare(null);
                $position->setCurrency(null);
                $position->setTransactions(new ArrayCollection());
                $activePositions[] = $position;
            }
        }

        return View::create($activePositions, Response::HTTP_CREATED);
    }


    /**
     * @Rest\Post("/position/bunch", name="create_position_bunch")
     * @param Request $request
     * @return View
     * @throws \Exception
     */
    public function persistPositionsBunch(Request $request): View
    {
        $portfolio = $this->getPortfolioByAuth($request);

        $serializer = SerializerBuilder::create()->build();
        $rawPositions = json_decode($request->getContent());
        $bankAccount = null;
        /** @var Position[] $positions */
        $positions = [];
        foreach($rawPositions as $rawPosition) {
            $position = $serializer->deserialize(json_encode($rawPosition), Position::class, 'json');
            if (null !== $position->getBankAccount()) {
                $portfolio = $this->getPortfolioByAuth($request); // do it again
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
        $portfolio = $this->getPortfolioByAuth($request);

        $position = $this->deserializePosition($request, $portfolio);
        $this->persistShare($portfolio, $position);
        $this->persistCurrency($portfolio, $position, $position);

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
        $portfolio = $this->getPortfolioByAuth($request);

        $position = $this->deserializePosition($request, $portfolio);
        $this->persistCurrency($portfolio, $position, $position);

        // happens in case of a import
        if ($position->getShare()->getId() == 0) {
            $position->setShare(null);
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
    public function updatePosition(Request $request, int $positionId, BalanceService $balanceService): View
    {
        $portfolio = $this->getPortfolioByAuth($request);

        $serializer = SerializerBuilder::create()->build();
        $content = json_decode($request->getContent());
        $removeUnderlying = false;
        if (isset($content->removeUnderlying)) {
            $removeUnderlying = true;
        }
        unset($content->balance);
        unset($content->transactions);
        unset($content->underlying);
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
            $oldPosition->getShare()->setShortname($newPosition->getShare()->getShortname());
            $oldPosition->getShare()->setIsin($newPosition->getShare()->getIsin());
            if ($oldPosition->getManualDrawdown() != $newPosition->getManualDrawdown()) {
                if ($newPosition->getManualDrawdown() > 0) {
                    $this->addPositionLogEntry('Setze manuellen Drawdown auf: ' . $newPosition->getManualDrawdown() . '%', $oldPosition);
                } else {
                    $this->addPositionLogEntry('Entferne manuellen Drawdown', $oldPosition);
                }
            }
            $oldPosition->setManualDrawdown($newPosition->getManualDrawdown());

            if ($oldPosition->getManualDividendDrop() !== $newPosition->getManualDividendDrop()) {
                if (is_int($newPosition->getManualDividendDrop()) > 0) {
                    $this->addPositionLogEntry('Setze manuellen Dividend Drop auf: ' . $newPosition->getManualDividendDrop() . '%', $oldPosition);
                } else {
                    $this->addPositionLogEntry('Entferne manuellen Dividend Drop', $oldPosition);
                }
            }
            $oldPosition->setManualDividendDrop($newPosition->getManualDividendDrop());

            if ($oldPosition->getManualDividend() !== $newPosition->getManualDividend()) {
                if ($newPosition->getManualDividend() > 0) {
                    $this->addPositionLogEntry('Setze manuelle Dividende auf: ' . $newPosition->getManualDividend(), $oldPosition);
                } else {
                    $this->addPositionLogEntry('Entferne manuelle Dividende', $oldPosition);
                }
            }
            $oldPosition->setManualDividend($newPosition->getManualDividend());

            if ($oldPosition->getManualTargetPrice() !== $newPosition->getManualTargetPrice()) {
                if ($newPosition->getManualTargetPrice() > 0) {
                    $this->addPositionLogEntry('Setze manuellen Target-Price auf: ' . $newPosition->getManualTargetPrice(), $oldPosition);
                } else {
                    $this->addPositionLogEntry('Entferne manuellen Target-Price', $oldPosition);
                }
            }
            $oldPosition->setManualTargetPrice($newPosition->getManualTargetPrice());

            if ($oldPosition->getStopLoss() !== $newPosition->getStopLoss()) {
                if ($newPosition->getStopLoss() > 0) {
                    $this->addPositionLogEntry('Setze Stop-Loss auf: ' . $newPosition->getStopLoss(), $oldPosition);
                } else {
                    $this->addPositionLogEntry('Entferne Stop-Loss', $oldPosition);
                }
            }
            $oldPosition->setStopLoss($newPosition->getStopLoss());

            if ($oldPosition->getTargetPrice() !== $newPosition->getTargetPrice() || $oldPosition->getTargetType() !== $newPosition->getTargetType()) {
                if ($newPosition->getTargetPrice() > 0) {
                    $this->addPositionLogEntry('Setze Target-Price (' . $newPosition->getTargetType() . ') auf: ' . $newPosition->getTargetPrice(), $oldPosition);
                } else {
                    $this->addPositionLogEntry('Entferne Target-Price', $oldPosition);
                    $newPosition->setTargetType(null);
                    $newPosition->setTargetPrice(null);
                }
            }
            $oldPosition->setTargetPrice($newPosition->getTargetPrice());
            $oldPosition->setTargetType($newPosition->getTargetType());

            $marketplace = $this->getDoctrine()->getRepository(Marketplace::class)->find($newPosition->getShare()->getMarketplace()->getId());
            $oldPosition->getShare()->setMarketplace($marketplace);

            $sector = null;
            if ($newPosition->getSector()) {
                $sector = $this->getDoctrine()->getRepository(Sector::class)->find($newPosition->getSector()->getId());
            }
            if ($oldPosition->getSector() != $sector) {
                if (null === $sector && null !== $oldPosition->getSector()) {
                    $this->addPositionLogEntry('Entferne zugewiesenen Sektor: ' . $oldPosition->getSector()->getName(), $oldPosition);
                } elseif (null !== $sector) {
                    $this->addPositionLogEntry('Ändere den zugewiesenen Sektor auf: ' . $sector->getName(), $oldPosition);
                }
            }
            $oldPosition->setSector($sector);

            $strategy = null;
            if ($newPosition->getStrategy()) {
                $strategy = $this->getDoctrine()->getRepository(Strategy::class)->find($newPosition->getStrategy()->getId());
            }
            if ($oldPosition->getStrategy() != $strategy) {
                if (null === $strategy && null !== $oldPosition->getStrategy()) {
                    $this->addPositionLogEntry('Entferne zugewiesene Strategie: ' . $oldPosition->getStrategy()->getName(), $oldPosition);
                } elseif (null !== $strategy) {
                    $this->addPositionLogEntry('Ändere die zugewiesene Strategie auf: ' . $strategy->getName(), $oldPosition);
                }
            }
            $oldPosition->setStrategy($strategy);

            if ($removeUnderlying) {
                $obsoletePosition = $oldPosition->getUnderlying();
                $oldPosition->setUnderlying(null);
                $this->getDoctrine()->getManager()->remove($obsoletePosition);
            }

            $this->persistCurrency($portfolio, $newPosition, $oldPosition);

            $this->getDoctrine()->getManager()->persist($oldPosition);

            $this->makeLogEntry('update position', $oldPosition);

            $this->getDoctrine()->getManager()->flush();
        }

        $oldPosition->setBankAccount(null);

        $balance = $balanceService->getBalanceForPosition($oldPosition);
        $oldPosition->setBalance($balance);
        $oldPosition->setLogEntries(new ArrayCollection(array_reverse($oldPosition->getLogEntries()->toArray())));

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
        $portfolio = $this->getPortfolioByAuth($request);

        $position = $this->getDoctrine()->getRepository(Position::class)->find($positionId);
        $this->getDoctrine()->getManager()->remove($position);

        $this->makeLogEntry('delete position', $position);

        $this->getDoctrine()->getManager()->flush();

        return new View("Position Delete Successfully", Response::HTTP_OK);
    }


    /**
     * @Rest\Get("/position/{positionId}/label/{labelId}", name="add_position_label")
     * @param Request $request
     * @param int $positionId
     * @param int $labelId
     * @return View
     */
    public function addPositionLabel(Request $request, int $positionId, int $labelId): View
    {
        $portfolio = $this->getPortfolioByAuth($request);

        /** @var Position $position */
        $position = $this->getDoctrine()->getRepository(Position::class)->find($positionId);
        $label = $this->getDoctrine()->getRepository(Label::class)->find($labelId);
        $position->addLabel($label);
        $this->getDoctrine()->getManager()->persist($position);

        $this->makeLogEntry('add label', $position);

        $this->getDoctrine()->getManager()->flush();

        $this->addPositionLogEntry('Füge Label hinzu: ' . $label->getName(), $position);

        return new View("Label Removed Successfully", Response::HTTP_OK);
    }


    /**
     * @Rest\Delete("/position/{positionId}/label/{labelId}", name="delete_position_label")
     * @param Request $request
     * @param int $positionId
     * @param int $labelId
     * @return View
     */
    public function deletePositionLabel(Request $request, int $positionId, int $labelId): View
    {
        $portfolio = $this->getPortfolioByAuth($request);

        /** @var Position $position */
        $position = $this->getDoctrine()->getRepository(Position::class)->find($positionId);
        $label = $this->getDoctrine()->getRepository(Label::class)->find($labelId);
        $position->removeLabel($label);
        $this->getDoctrine()->getManager()->persist($position);

        $this->makeLogEntry('removed label', $position);

        $this->getDoctrine()->getManager()->flush();

        $this->addPositionLogEntry('Entferne Label: ' . $label->getName(), $position);

        return new View("Label Removed Successfully", Response::HTTP_OK);
    }


    /**
     * @param Request $request
     * @param Portfolio $portfolio
     * @return Position
     */
    private function deserializePosition(Request $request, Portfolio $portfolio): Position
    {
        $serializer = SerializerBuilder::create()->build();
        /** @var Position $position */
        $position = $serializer->deserialize($request->getContent(), Position::class, 'json');

        $bankAccount = null;
        $motherPosition = null;
        if ($position->getBankAccount()) {
            $bankAccount = $portfolio->getBankAccountById($position->getBankAccount()->getId());
        }
        if ($position->getMotherId() > 0) {
            $motherPosition = $portfolio->getPositionById($position->getMotherId());
        }
        if (null === $bankAccount && null === $motherPosition) {
            throw new AccessDeniedException();
        } else {
            $position->setBankAccount($bankAccount);
            if (null !== $motherPosition) {
                $motherPosition->setUnderlying($position);
            }
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
