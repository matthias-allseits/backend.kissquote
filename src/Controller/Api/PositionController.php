<?php

namespace App\Controller\Api;

use App\Entity\Label;
use App\Entity\Marketplace;
use App\Entity\Portfolio;
use App\Entity\Position;
use App\Entity\Sector;
use App\Entity\Strategy;
use App\Service\BalanceService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
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
     * @param EntityManagerInterface $entityManager
     * @return View
     */
    public function getPosition(Request $request, int $positionId, BalanceService $balanceService, EntityManagerInterface $entityManager): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

        $position = $entityManager->getRepository(Position::class)->find($positionId);
        if ($position->getBankAccount()) {
            $position->setBankAccountName($position->getBankAccount()->getName());
        }
        $position->setBankAccount(null);

        $motherPosition = $entityManager->getRepository(Position::class)->findOneBy(['underlying' => $position]);
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
     * @param EntityManagerInterface $entityManager
     * @return View
     */
    public function listPositions(Request $request, EntityManagerInterface $entityManager): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

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
     * @param EntityManagerInterface $entityManager
     * @return View
     */
    public function listActivePositions(Request $request, EntityManagerInterface $entityManager): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

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
     * @param EntityManagerInterface $entityManager
     * @return View
     */
    public function persistPositionsBunch(Request $request, EntityManagerInterface $entityManager): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

        $serializer = SerializerBuilder::create()->build();
        $rawPositions = json_decode($request->getContent());
        $bankAccount = null;
        /** @var Position[] $positions */
        $positions = [];
        foreach($rawPositions as $rawPosition) {
            $position = $serializer->deserialize(json_encode($rawPosition), Position::class, 'json');
            if (null !== $position->getBankAccount()) {
                $portfolio = $this->getPortfolioByAuth($request, $entityManager); // do it again
                $bankAccount = $portfolio->getBankAccountById($position->getBankAccount()->getId());
                if (null === $bankAccount) {
                    throw new AccessDeniedException();
                }
                $position->setBankAccount($bankAccount);

                $this->persistShare($portfolio, $position, $entityManager);
                $this->persistCurrency($portfolio, $position, $position, $entityManager);

                // happens in case of a import
                if (count($position->getTransactions()) > 0) {
                    $this->persistTransactions($position, $portfolio, $entityManager);
                }
                $entityManager->persist($position);
                $entityManager->flush();
            }
        }

        $this->makeLogEntry('persist a bunch of positions', 'bunch-persisting: there will be more than one', $entityManager);

        $entityManager->flush();

        return View::create($positions, Response::HTTP_CREATED);
    }


    /**
     * @Rest\Post("/position", name="create_position")
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return View
     */
    public function createPosition(Request $request, EntityManagerInterface $entityManager): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

        $position = $this->deserializePosition($request, $portfolio, $entityManager);
        $this->persistShare($portfolio, $position, $entityManager);
        $this->persistCurrency($portfolio, $position, $position, $entityManager);

        $entityManager->persist($position);

        $this->makeLogEntry('create new position', $position, $entityManager);

        $entityManager->flush();

        $position->setBankAccount(null);

        return View::create($position, Response::HTTP_OK);
    }


    /**
     * @Rest\Post("/position/cash", name="create_cash_position")
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return View
     */
    public function createCashPosition(Request $request, EntityManagerInterface $entityManager): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

        $position = $this->deserializePosition($request, $portfolio, $entityManager);
        $this->persistCurrency($portfolio, $position, $position, $entityManager);

        // happens in case of a import
        if ($position->getShare()->getId() == 0) {
            $position->setShare(null);
        }

        $entityManager->persist($position);
        $entityManager->flush();

        $position->setBankAccount(null);

        return View::create($position, Response::HTTP_OK);
    }


    /**
     * @Rest\Put("/position/{positionId}", name="update_position")
     * @param Request $request
     * @param int $positionId
     * @param BalanceService $balanceService
     * @param EntityManagerInterface $entityManager
     * @return View
     */
    public function updatePosition(Request $request, int $positionId, BalanceService $balanceService, EntityManagerInterface $entityManager): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

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
        $oldPosition = $entityManager->getRepository(Position::class)->find($newPosition->getId());
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
                    $this->addPositionLogEntry('Setze manuellen Drawdown auf: ' . $newPosition->getManualDrawdown() . '%', $oldPosition, $entityManager);
                } else {
                    $this->addPositionLogEntry('Entferne manuellen Drawdown', $oldPosition, $entityManager);
                }
            }
            $oldPosition->setManualDrawdown($newPosition->getManualDrawdown());

            if ($oldPosition->getManualDividendDrop() !== $newPosition->getManualDividendDrop()) {
                if (is_int($newPosition->getManualDividendDrop()) > 0) {
                    $this->addPositionLogEntry('Setze manuellen Dividend Drop auf: ' . $newPosition->getManualDividendDrop() . '%', $oldPosition, $entityManager);
                } else {
                    $this->addPositionLogEntry('Entferne manuellen Dividend Drop', $oldPosition, $entityManager);
                }
            }
            $oldPosition->setManualDividendDrop($newPosition->getManualDividendDrop());

            if ($oldPosition->getManualDividend() !== $newPosition->getManualDividend()) {
                if ($newPosition->getManualDividend() > 0) {
                    $this->addPositionLogEntry('Setze manuelle Dividende auf: ' . $newPosition->getManualDividend(), $oldPosition, $entityManager);
                } else {
                    $this->addPositionLogEntry('Entferne manuelle Dividende', $oldPosition, $entityManager);
                }
            }
            $oldPosition->setManualDividend($newPosition->getManualDividend());

            if ($oldPosition->getManualTargetPrice() !== $newPosition->getManualTargetPrice()) {
                if ($newPosition->getManualTargetPrice() > 0) {
                    $this->addPositionLogEntry('Setze manuellen Target-Price auf: ' . $newPosition->getManualTargetPrice(), $oldPosition, $entityManager);
                } else {
                    $this->addPositionLogEntry('Entferne manuellen Target-Price', $oldPosition, $entityManager);
                }
            }
            $oldPosition->setManualTargetPrice($newPosition->getManualTargetPrice());

            if ($oldPosition->getStopLoss() !== $newPosition->getStopLoss()) {
                if ($newPosition->getStopLoss() > 0) {
                    $this->addPositionLogEntry('Setze Stop-Loss auf: ' . $newPosition->getStopLoss(), $oldPosition, $entityManager);
                } else {
                    $this->addPositionLogEntry('Entferne Stop-Loss', $oldPosition, $entityManager);
                }
            }
            $oldPosition->setStopLoss($newPosition->getStopLoss());

            if ($oldPosition->getTargetPrice() !== $newPosition->getTargetPrice() || $oldPosition->getTargetType() !== $newPosition->getTargetType()) {
                if ($newPosition->getTargetPrice() > 0) {
                    $this->addPositionLogEntry('Setze Target-Price (' . $newPosition->getTargetType() . ') auf: ' . $newPosition->getTargetPrice(), $oldPosition, $entityManager);
                } else {
                    $this->addPositionLogEntry('Entferne Target-Price', $oldPosition, $entityManager);
                    $newPosition->setTargetType(null);
                    $newPosition->setTargetPrice(null);
                }
            }
            $oldPosition->setTargetPrice($newPosition->getTargetPrice());
            $oldPosition->setTargetType($newPosition->getTargetType());

            $marketplace = $entityManager->getRepository(Marketplace::class)->find($newPosition->getShare()->getMarketplace()->getId());
            $oldPosition->getShare()->setMarketplace($marketplace);

            $sector = null;
            if ($newPosition->getSector()) {
                $sector = $entityManager->getRepository(Sector::class)->find($newPosition->getSector()->getId());
            }
            if ($oldPosition->getSector() != $sector) {
                if (null === $sector && null !== $oldPosition->getSector()) {
                    $this->addPositionLogEntry('Entferne zugewiesenen Sektor: ' . $oldPosition->getSector()->getName(), $oldPosition, $entityManager);
                } elseif (null !== $sector) {
                    $this->addPositionLogEntry('Ändere den zugewiesenen Sektor auf: ' . $sector->getName(), $oldPosition, $entityManager);
                }
            }
            $oldPosition->setSector($sector);

            $strategy = null;
            if ($newPosition->getStrategy()) {
                $strategy = $entityManager->getRepository(Strategy::class)->find($newPosition->getStrategy()->getId());
            }
            if ($oldPosition->getStrategy() != $strategy) {
                if (null === $strategy && null !== $oldPosition->getStrategy()) {
                    $this->addPositionLogEntry('Entferne zugewiesene Strategie: ' . $oldPosition->getStrategy()->getName(), $oldPosition, $entityManager);
                } elseif (null !== $strategy) {
                    $this->addPositionLogEntry('Ändere die zugewiesene Strategie auf: ' . $strategy->getName(), $oldPosition, $entityManager);
                }
            }
            $oldPosition->setStrategy($strategy);

            if ($removeUnderlying) {
                $obsoletePosition = $oldPosition->getUnderlying();
                $oldPosition->setUnderlying(null);
                $entityManager->remove($obsoletePosition);
            }

            $this->persistCurrency($portfolio, $newPosition, $oldPosition, $entityManager);

            $entityManager->persist($oldPosition);

            $this->makeLogEntry('update position', $oldPosition, $entityManager);

            $entityManager->flush();
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
     * @param EntityManagerInterface $entityManager
     * @return View
     */
    public function deletePosition(Request $request, int $positionId, EntityManagerInterface $entityManager): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

        $position = $entityManager->getRepository(Position::class)->find($positionId);
        $entityManager->remove($position);

        $this->makeLogEntry('delete position', $position, $entityManager);

        $entityManager->flush();

        return new View("Position Delete Successfully", Response::HTTP_OK);
    }


    /**
     * @Rest\Get("/position/{positionId}/label/{labelId}", name="add_position_label")
     * @param Request $request
     * @param int $positionId
     * @param int $labelId
     * @param EntityManagerInterface $entityManager
     * @return View
     */
    public function addPositionLabel(Request $request, int $positionId, int $labelId, EntityManagerInterface $entityManager): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

        /** @var Position $position */
        $position = $entityManager->getRepository(Position::class)->find($positionId);
        $label = $entityManager->getRepository(Label::class)->find($labelId);
        $position->addLabel($label);
        $entityManager->persist($position);

        $this->makeLogEntry('add label', $position, $entityManager);

        $entityManager->flush();

        $this->addPositionLogEntry('Füge Label hinzu: ' . $label->getName(), $position, $entityManager);

        return new View("Label Removed Successfully", Response::HTTP_OK);
    }


    /**
     * @Rest\Delete("/position/{positionId}/label/{labelId}", name="delete_position_label")
     * @param Request $request
     * @param int $positionId
     * @param int $labelId
     * @param EntityManagerInterface $entityManager
     * @return View
     */
    public function deletePositionLabel(Request $request, int $positionId, int $labelId, EntityManagerInterface $entityManager): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

        /** @var Position $position */
        $position = $entityManager->getRepository(Position::class)->find($positionId);
        $label = $entityManager->getRepository(Label::class)->find($labelId);
        $position->removeLabel($label);
        $entityManager->persist($position);

        $this->makeLogEntry('removed label', $position, $entityManager);

        $entityManager->flush();

        $this->addPositionLogEntry('Entferne Label: ' . $label->getName(), $position, $entityManager);

        return new View("Label Removed Successfully", Response::HTTP_OK);
    }


    /**
     * @param Request $request
     * @param Portfolio $portfolio
     * @param EntityManagerInterface $entityManager
     * @return Position
     */
    private function deserializePosition(Request $request, Portfolio $portfolio, EntityManagerInterface $entityManager): Position
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
     * @param EntityManagerInterface $entityManager
     */
    private function persistTransactions(Position $position, Portfolio $portfolio, EntityManagerInterface $entityManager): void
    {
        $entityManager->persist($position);
        $persistedTransactions = [];
        foreach ($position->getTransactions() as $transaction) {
            $transactionCurrency = $portfolio->getCurrencyByName($transaction->getCurrency()->getName());
            if (null === $transactionCurrency) {
                $transactionCurrency = $transaction->getCurrency();
                $transactionCurrency->setPortfolioId($portfolio->getId());
                $entityManager->persist($transactionCurrency);
            }
            $transaction->setCurrency($transactionCurrency);

            $transaction->setQuantity(abs($transaction->getQuantity()));
            $transaction->setPosition($position);
            $entityManager->persist($transaction);
            $persistedTransactions[] = $transaction;
        }
        $position->setTransactions($persistedTransactions);
    }


    /**
     * @param Portfolio $portfolio
     * @param Position $position
     * @param EntityManagerInterface $entityManager
     */
    private function persistShare(Portfolio $portfolio, Position $position, EntityManagerInterface $entityManager): void
    {
        // todo: get share by isin is not enough. currency is missing
        $share = $portfolio->getShareByIsin($position->getShare()->getIsin());
        if (null === $share) {
            $share = $position->getShare();
            if (strlen($share->getShortname()) == 0) {
                $share->setShortname(substr($share->getName(), 0, 15));
            }
            $marketplace = $entityManager->getRepository(Marketplace::class)->find($share->getMarketplace()->getId());
            $share->setMarketplace($marketplace);
            $share->setPortfolioId($portfolio->getId());
            $entityManager->persist($share);
        }
        $position->setShare($share);
    }


    /**
     * @param Portfolio $portfolio
     * @param Position $sourcePosition
     * @param Position $targetPosition
     * @param EntityManagerInterface $entityManager
     */
    private function persistCurrency(Portfolio $portfolio, Position $sourcePosition, Position $targetPosition, EntityManagerInterface $entityManager): void
    {
        $currency = $portfolio->getCurrencyByName($sourcePosition->getCurrency()->getName());
        if (null === $currency) {
            $currency = $sourcePosition->getCurrency();
            $currency->setPortfolioId($portfolio->getId());
            $entityManager->persist($currency);
        }
        $targetPosition->setCurrency($currency);
        if (null !== $targetPosition->getShare()) {
            $targetPosition->getShare()->setCurrency($currency);
        }
    }

}
