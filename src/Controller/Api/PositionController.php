<?php

namespace App\Controller\Api;

use App\Entity\BankAccount;
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
use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Serializer\SerializerInterface;


class PositionController extends BaseController
{

    #[Route('/api/position/{positionId}', name: 'get_position', requirements: ['positionId' => '\d+'], methods: ['GET', 'OPTIONS'])]
    public function getPosition(Request $request, int $positionId, BalanceService $balanceService, EntityManagerInterface $entityManager, SerializerInterface $serializer): Response
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

        $position = $entityManager->getRepository(Position::class)->find($positionId);

        if ($position->getBankAccount()) {
            $position->setBankAccountName($position->getBankAccount()->getName());
        }

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

        $data = $serializer->serialize($position, 'json',
//            ['groups' => ['product-view']]
        );

        return new Response($data);
    }


    #[Route('/api/position/active', name: 'list_active_positions', methods: ['GET', 'OPTIONS'])]
    public function listActivePositions(Request $request, EntityManagerInterface $entityManager): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

        $positions = $portfolio->getAllPositions();
        $activePositions = [];
        foreach($positions as $position) {
            if ($position->isActive()) {
                $position->setBankAccount(null);
//                $position->setShare(null);
//                $position->setCurrency(null);
                $position->setTransactions(new ArrayCollection());
                $activePositions[] = $position;
            }
        }

        return View::create($activePositions, Response::HTTP_CREATED);
    }


    // todo: probably only used at portfolio-import I will test laaater...
    /**
     * @Rest\Post("/position/bunch", name="create_position_bunch")
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return View
     */
    public function persistPositionsBunch(Request $request, EntityManagerInterface $entityManager, SerializerInterface $serializer): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

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


    #[Route('/api/position', name: 'create_position', methods: ['POST', 'OPTIONS'])]
    public function createPosition(Request $request, EntityManagerInterface $entityManager, SerializerInterface $serializer): View
    {
        list($portfolio, $position) = $this->preparePositionCreation($request, $entityManager, $serializer);

        $this->persistShare($portfolio, $position, $entityManager);
        $this->persistCurrency($portfolio, $position, $position, $entityManager);

        $entityManager->persist($position);

        $this->makeLogEntry('create new position', $position, $entityManager);

        $entityManager->flush();

        $position->setBankAccount(null);

        return View::create($position, Response::HTTP_OK);
    }


    #[Route('/api/position/cash', name: 'create_cash_position', methods: ['POST', 'OPTIONS'])]
    public function createCashPosition(Request $request, EntityManagerInterface $entityManager, SerializerInterface $serializer): View
    {
        list($portfolio, $position) = $this->preparePositionCreation($request, $entityManager, $serializer);

        $this->persistCurrency($portfolio, $position, $position, $entityManager);

        // happens in case of a import
        if ($position->getShare() && $position->getShare()->getId() == 0) {
            $position->setShare(null);
        }

        $entityManager->persist($position);
        $entityManager->flush();

        $position->setBankAccount(null);

        return View::create($position, Response::HTTP_OK);
    }


    #[Route('/api/position/{positionId}', name: 'update_position', methods: ['PUT', 'OPTIONS'])]
    public function updatePosition(Request $request, int $positionId, BalanceService $balanceService, EntityManagerInterface $entityManager, SerializerInterface $serializer): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

        $content = json_decode($request->getContent());
        $removeUnderlying = false;
        if (isset($content->removeUnderlying)) {
            $removeUnderlying = true;
        }
        unset($content->balance);
        unset($content->transactions);
        unset($content->underlying);
//        var_dump($content);
        /** @var Position $puttedPosition */
        $puttedPosition = $serializer->deserialize(json_encode($content), Position::class, 'json');

        /** @var Position $oldPosition */
        $oldPosition = $entityManager->getRepository(Position::class)->find($positionId);
        if (null !== $oldPosition) {
            $oldPosition->setDividendPeriodicity($puttedPosition->getDividendPeriodicity());
            $oldPosition->setActiveFrom($puttedPosition->getActiveFrom());
            $oldPosition->setActiveUntil($puttedPosition->getActiveUntil());
            $oldPosition->setActive($puttedPosition->isActive());
            $oldPosition->setShareheadId($puttedPosition->getShareheadId());
            $oldPosition->getShare()->setName($puttedPosition->getShare()->getName());
            $oldPosition->getShare()->setShortname($puttedPosition->getShare()->getShortname());
            $oldPosition->getShare()->setIsin($puttedPosition->getShare()->getIsin());
            if ($oldPosition->getManualDrawdown() != $puttedPosition->getManualDrawdown()) {
                if ($puttedPosition->getManualDrawdown() > 0) {
                    $this->addPositionLogEntry('Setze manuellen Drawdown auf: ' . $puttedPosition->getManualDrawdown() . '%', $oldPosition, $entityManager);
                } else {
                    $this->addPositionLogEntry('Entferne manuellen Drawdown', $oldPosition, $entityManager);
                }
            }
            $oldPosition->setManualDrawdown($puttedPosition->getManualDrawdown());

            if ($oldPosition->getManualDividendDrop() !== $puttedPosition->getManualDividendDrop()) {
                if (is_int($puttedPosition->getManualDividendDrop()) > 0) {
                    $this->addPositionLogEntry('Setze manuellen Dividend Drop auf: ' . $puttedPosition->getManualDividendDrop() . '%', $oldPosition, $entityManager);
                } else {
                    $this->addPositionLogEntry('Entferne manuellen Dividend Drop', $oldPosition, $entityManager);
                }
            }
            $oldPosition->setManualDividendDrop($puttedPosition->getManualDividendDrop());

            if ($oldPosition->getManualDividend() !== $puttedPosition->getManualDividend()) {
                if ($puttedPosition->getManualDividend() > 0) {
                    $this->addPositionLogEntry('Setze manuelle Dividende auf: ' . $puttedPosition->getManualDividend(), $oldPosition, $entityManager);
                } else {
                    $this->addPositionLogEntry('Entferne manuelle Dividende', $oldPosition, $entityManager);
                }
            }
            $oldPosition->setManualDividend($puttedPosition->getManualDividend());

            if ($oldPosition->getManualTargetPrice() !== $puttedPosition->getManualTargetPrice()) {
                if ($puttedPosition->getManualTargetPrice() > 0) {
                    $this->addPositionLogEntry('Setze manuellen Target-Price auf: ' . $puttedPosition->getManualTargetPrice(), $oldPosition, $entityManager);
                } else {
                    $this->addPositionLogEntry('Entferne manuellen Target-Price', $oldPosition, $entityManager);
                }
            }
            $oldPosition->setManualTargetPrice($puttedPosition->getManualTargetPrice());

            if ($oldPosition->getStopLoss() !== $puttedPosition->getStopLoss()) {
                if ($puttedPosition->getStopLoss() > 0) {
                    $this->addPositionLogEntry('Setze Stop-Loss auf: ' . $puttedPosition->getStopLoss(), $oldPosition, $entityManager);
                } else {
                    $this->addPositionLogEntry('Entferne Stop-Loss', $oldPosition, $entityManager);
                }
            }
            $oldPosition->setStopLoss($puttedPosition->getStopLoss());

            if ($oldPosition->getTargetPrice() !== $puttedPosition->getTargetPrice() || $oldPosition->getTargetType() !== $puttedPosition->getTargetType()) {
                if ($puttedPosition->getTargetPrice() > 0) {
                    $this->addPositionLogEntry('Setze Target-Price (' . $puttedPosition->getTargetType() . ') auf: ' . $puttedPosition->getTargetPrice(), $oldPosition, $entityManager);
                } else {
                    $this->addPositionLogEntry('Entferne Target-Price', $oldPosition, $entityManager);
                    $puttedPosition->setTargetType(null);
                    $puttedPosition->setTargetPrice(null);
                }
            }
            $oldPosition->setTargetPrice($puttedPosition->getTargetPrice());
            $oldPosition->setTargetType($puttedPosition->getTargetType());

            $marketplace = $entityManager->getRepository(Marketplace::class)->find($puttedPosition->getShare()->getMarketplace()->getId());
            $oldPosition->getShare()->setMarketplace($marketplace);

            $sector = null;
            if ($puttedPosition->getSector()) {
                $sector = $entityManager->getRepository(Sector::class)->find($puttedPosition->getSector()->getId());
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
            if ($puttedPosition->getStrategy()) {
                $strategy = $entityManager->getRepository(Strategy::class)->find($puttedPosition->getStrategy()->getId());
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

            $this->persistCurrency($portfolio, $puttedPosition, $oldPosition, $entityManager);

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


    #[Route('/api/position/{positionId}', name: 'delete_position', methods: ['DELETE', 'OPTIONS'])]
    public function deletePosition(Request $request, int $positionId, EntityManagerInterface $entityManager): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

        $position = $entityManager->getRepository(Position::class)->find($positionId);
        $position->setBankAccount(null);
        $entityManager->remove($position);

        $this->makeLogEntry('delete position', $position, $entityManager);

        $entityManager->flush();

        return new View("Position Delete Successfully", Response::HTTP_OK);
    }


    #[Route('/api/position/{positionId}/label/{labelId}', name: 'add_position_label', methods: ['GET', 'OPTIONS'])]
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

        return new View("Label Added Successfully", Response::HTTP_OK);
    }


    #[Route('/api/position/{positionId}/label/{labelId}', name: 'delete_position_label', methods: ['DELETE', 'OPTIONS'])]
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


    #[Route('/api/position/{positionId}/toggle-markable/{key}', name: 'toggle_position_markables', methods: ['GET', 'OPTIONS'])]
    public function togglePositionMarkable(Request $request, int $positionId, string $key, EntityManagerInterface $entityManager): View
    {
        /** @var Position $position */
        $position = $entityManager->getRepository(Position::class)->find($positionId);
        $position->toggleMarkable($key);
        $entityManager->persist($position);
        $entityManager->flush();

        return new View("Markable Toggled Successfully", Response::HTTP_OK);
    }


    private function preparePositionCreation(Request $request, EntityManagerInterface $entityManager, SerializerInterface $serializer): array
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

        // todo: probably no longer necessary
        $content = json_decode($request->getContent());
        $bankAccount = $serializer->deserialize(json_encode($content->bankAccount), BankAccount::class, 'json');
        $content->bankAccount = null;
        $position = $serializer->deserialize(json_encode($content), Position::class, 'json');

        $motherPosition = null;
        if ($bankAccount) {
            $bankAccount = $portfolio->getBankAccountById($bankAccount->getId());
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

        return array($portfolio, $position);
    }


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
