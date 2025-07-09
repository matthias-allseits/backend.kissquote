<?php

namespace App\Controller\Api;

use App\Entity\BankAccount;
use App\Entity\Currency;
use App\Entity\Label;
use App\Entity\ManualDividend;
use App\Entity\Portfolio;
use App\Entity\Position;
use App\Entity\PositionLog;
use App\Entity\Sector;
use App\Entity\Share;
use App\Entity\Strategy;
use App\Entity\Transaction;
use App\Entity\Watchlist;
use App\Helper\RandomizeHelper;
use App\Service\BalanceService;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;


class PortfolioController extends BaseController
{

    #[Route('/api/portfolio', name: 'create_portfolio', methods: ['POST', 'OPTIONS'])]
    public function createPortfolio(Request $request, EntityManagerInterface $entityManager): View
    {
        // todo: implement missing try catch loop since the randoms will not be unique...
        $randomUserName = RandomizeHelper::getRandomUserName();
        $randomHashKey = RandomizeHelper::getRandomHashKey();

        $portfolio = new Portfolio();
        $portfolio->setUserName($randomUserName);
        $portfolio->setHashKey($randomHashKey);
        $portfolio->setStartDate(new \DateTime());

        $bankAccount = new BankAccount();
        $bankAccount->setName('Meine Bank A');
        $bankAccount->setPortfolio($portfolio);
        $portfolio->addBankAccount($bankAccount);
        $this->portfolio = $portfolio;

        $entityManager->persist($portfolio);
        $entityManager->persist($bankAccount);
        $entityManager->flush();

        $this->persistDefaultCurrencies($portfolio, $entityManager);
        $this->persistDefaultLabels($portfolio, $entityManager);
        $this->persistDefaultSectors($portfolio, $entityManager);

        $this->makeLogEntry('create new portfolio', $portfolio, $entityManager);

        $entityManager->flush();

        return View::create($portfolio, Response::HTTP_CREATED);
    }


    #[Route('/api/portfolio/restore', name: 'restore_portfolio', methods: ['POST', 'OPTIONS'])]
    public function restorePortfolio(Request $request, BalanceService $balanceService, EntityManagerInterface $entityManager): View
    {
        // todo: implement a better solution
        $content = json_decode($request->getContent());

        $portfolio = $this->getPortfolioWithBalances($content->hashKey, $balanceService, $entityManager);

        return View::create($portfolio, Response::HTTP_OK);
    }


    #[Route('/api/portfolio/time-warp', name: 'timewarp_portfolio', methods: ['POST', 'OPTIONS'])]
    public function timewarpedPortfolio(Request $request, BalanceService $balanceService, EntityManagerInterface $entityManager): View
    {
        $key = $request->headers->get('Authorization');
        $body = json_decode($request->getContent(), false);
        $timeWarpDate = new \DateTime($body->date);

        $portfolio = $this->getPortfolioWithBalances($key, $balanceService, $entityManager);
        foreach($portfolio->getBankAccounts() as $account) {
            // todo: loop over positions and check the activeUntil to eventually reactivate closed positions
            foreach($account->getPositions() as $position) {
                if (null !== $position->getActiveUntil() && $position->getActiveUntil() > $timeWarpDate) {
                    $position->setActiveUntil(null);
                    $position->setActive(true);
                }
            }
            // todo: loop over positions and remove all activeFrom after the given date
            foreach($account->getPositions() as $position) {
                if ($position->getActiveFrom() > $timeWarpDate) {
                    $account->removePosition($position);
                }
            }
            // todo: loop over positions->transactions and remove all after the given date
            foreach($account->getPositions() as $position) {
                foreach($position->getTransactions() as $transaction) {
                    if ($transaction->getDate() > $timeWarpDate) {
                        $position->removeTransaction($transaction);
                    }
                }
            }
        }
        foreach($portfolio->getBankAccounts() as $account) {
            foreach ($account->getPositions() as $position) {
                $balance = $balanceService->getBalanceForPosition($position, $timeWarpDate);
                $position->setBalance($balance);
            }
        }

        return View::create($portfolio, Response::HTTP_OK);
    }


    #[Route('/api/portfolio/demo', name: 'create_demo_portfolio', methods: ['GET', 'OPTIONS'])]
    public function createDemoPortfolio(Request $request, EntityManagerInterface $entityManager): View
    {
        // todo: implement missing try catch loop since the randoms will not be unique...
        $randomUserName = RandomizeHelper::getRandomUserName();
        $randomHashKey = RandomizeHelper::getRandomHashKey();

        /** @var Portfolio $demoPortfolio */
        $demoPortfolio = $entityManager->getRepository(Portfolio::class)->findOneBy(['id' => 169]);
        $demoCurrencies = $entityManager->getRepository(Currency::class)->findBy(['portfolioId' => $demoPortfolio->getId()]);
        $demoPortfolio->setCurrencies($demoCurrencies);
        $demoShares = $entityManager->getRepository(Share::class)->findBy(['portfolioId' => $demoPortfolio->getId()]);
        $demoPortfolio->setShares($demoShares);

//        $newPortfolio = clone $demoPortfolio;
        $newPortfolio = new Portfolio();
        $newPortfolio->setUserName($randomUserName);
        $newPortfolio->setHashKey($randomHashKey);
        $newPortfolio->setStartDate(new \DateTime());
        $entityManager->persist($newPortfolio);
        $entityManager->flush();
        $this->portfolio = $newPortfolio;

        $newCurrencies = $this->persistDefaultCurrencies($newPortfolio, $entityManager);
        $newPortfolio->setCurrencies($newCurrencies);

        /** @var Label[] $demoLabels */
        $demoLabels = $entityManager->getRepository(Label::class)->findBy(['portfolioId' => $demoPortfolio->getId()]);
        $newLabels = [];
        foreach($demoLabels as $demoLabel) {
            $newLabel = clone $demoLabel;
            $newLabel->setPortfolioId($newPortfolio->getId());
            $entityManager->persist($newLabel);
            $newLabels[] = $newLabel;
        }
        $newPortfolio->setLabels($newLabels);

        /** @var Sector[] $demoSectors */
        $demoSectors = $entityManager->getRepository(Sector::class)->findBy(['portfolioId' => $demoPortfolio->getId()]);
        $newSectors = [];
        foreach($demoSectors as $sector) {
            $newSector = clone $sector;
            $newSector->setPortfolioId($newPortfolio->getId());
            $entityManager->persist($newSector);
            $newSectors[] = $newSector;
        }
        $newPortfolio->setSectors($newSectors);

        /** @var Strategy[] $demoStrategies */
        $demoStrategies = $entityManager->getRepository(Strategy::class)->findBy(['portfolioId' => $demoPortfolio->getId()]);
        $newStrategies = [];
        foreach($demoStrategies as $strategy) {
            $newStrategy = clone $strategy;
            $newStrategy->setPortfolioId($newPortfolio->getId());
            $entityManager->persist($newStrategy);
            $newStrategies[] = $newStrategy;
        }
        $newPortfolio->setStrategies($newStrategies);

        $newShares = [];
        foreach($demoPortfolio->getShares() as $share) {
            $newShare = new Share();
            $newShare->setName($share->getName());
            $newShare->setIsin($share->getIsin());
            $newShare->setMarketplace($share->getMarketplace());
            $newShare->setShortname($share->getShortname());
            $currency = $newPortfolio->getCurrencyByName($share->getCurrency()->getName());
            $newShare->setCurrency($currency);
            $newShare->setPortfolioId($newPortfolio->getId());

            $newManualDividends = [];
            foreach($share->getManualDividends() as $manualDividend) {
                $newManualDividend = new ManualDividend();
                $newManualDividend->setShare($newShare);
                $newManualDividend->setAmount($manualDividend->getAmount());
                $newManualDividend->setYear($manualDividend->getYear());
                $newManualDividends[] = $newManualDividend;
            }
            $newShare->setManualDividends($newManualDividends);

            $entityManager->persist($newShare);
            $newShares[] = $newShare;
        }
        $newPortfolio->setShares($newShares);

        $accountNames = ['Konto A', 'Konto B'];
        $newAccounts = [];
        foreach($demoPortfolio->getBankAccounts() as $i => $account) {
            $newAccount = new BankAccount();
            $newAccount->setPortfolio($newPortfolio);
            $newAccount->setName($accountNames[$i]);
            $entityManager->persist($newAccount);

            $newPositions = [];
            foreach($account->getPositions() as $position) {
                $newPosition = new Position();
                $newPosition->setBankAccount($newAccount);
                $newPosition->setTransactions([]);
                $newPosition->setActive($position->isActive());
                $newPosition->setActiveFrom($position->getActiveFrom());
                $newPosition->setActiveUntil($position->getActiveUntil());
                $newPosition->setShareheadId($position->getShareheadId());
                $newPosition->setDividendPeriodicity($position->getDividendPeriodicity());
                $newPosition->setManualDividendDrop($position->getManualDividendDrop());
                $newPosition->setManualDrawdown($position->getManualDrawdown());
                $newPosition->setStopLoss($position->getStopLoss());
                $newPosition->setTargetPrice($position->getTargetPrice());
                $newPosition->setTargetType($position->getTargetType());
                $newPosition->setManualDividend($position->getManualDividend());
                $newPosition->setManualTargetPrice($position->getManualTargetPrice());
                // todo: implement missing underlying copy
                $newPosition->setIsCash($position->isCash());
                $entityManager->persist($newPosition);

                $share = null;
                if (null !== $position->getShare()) {
                    $share = $newPortfolio->getShareByIsin($position->getShare()->getIsin());
                    $sharesCurrency = $newPortfolio->getCurrencyByName($share->getCurrency()->getName());
                    $share->setCurrency($sharesCurrency);
                }
                $newPosition->setShare($share);
                $currency = $newPortfolio->getCurrencyByName($position->getCurrency()->getName());
                $newPosition->setCurrency($currency);

                $newTransactions = [];
                foreach($position->getTransactions() as $transaction) {
                    $newTransaction = new Transaction();
                    $newTransaction->setPosition($newPosition);
                    $newTransaction->setDate($transaction->getDate());
                    $newTransaction->setTitle($transaction->getTitle());
                    $newTransaction->setQuantity($transaction->getQuantity());
                    $newTransaction->setRate($transaction->getRate());
                    $newTransaction->setFee($transaction->getFee());
                    $transactionCurrency = $newPortfolio->getCurrencyByName($transaction->getCurrency()->getName());
                    $newTransaction->setCurrency($transactionCurrency);
                    $newTransactions[] = $newTransaction;
                }
                $newPosition->setTransactions($newTransactions);

                $newLogEntries = [];
                foreach($position->getLogEntries() as $logEntry) {
                    if ($logEntry->isDemo()) {
                        $newLogEntry = new PositionLog();
                        $newLogEntry->setPosition($newPosition);
                        $newLogEntry->setDate($logEntry->getDate());
                        $newLogEntry->setLog($logEntry->getLog());
                        $newLogEntry->setEmoticon($logEntry->getEmoticon());
                        $newLogEntry->setPinned($logEntry->isPinned());
                        $newLogEntries[] = $newLogEntry;
                    }
                }
                $newPosition->setLogEntries($newLogEntries);

                $sector = null;
                if (null !== $position->getSector()) {
                    $sector = $newPortfolio->getSectorByName($position->getSector()->getName());
                }
                $newPosition->setSector($sector);

                $strategy = null;
                if (null !== $position->getStrategy()) {
                    $strategy = $newPortfolio->getStrategyByName($position->getStrategy()->getName());
                }
                $newPosition->setStrategy($strategy);

                foreach($position->getLabels() as $label) {
                    $label = $newPortfolio->getLabelByName($label->getName());
                    $newPosition->addLabel($label);
                }

                $newPositions[] = $newPosition;
            }
            $newAccount->setPositions($newPositions);

            $newAccounts[] = $newAccount;
        }
        $newPortfolio->setBankAccounts($newAccounts);

        $newWatchlistEntries = [];
        foreach($demoPortfolio->getWatchlistEntries() as $entry) {
            $newEntry = new Watchlist();
            $newEntry->setPortfolio($newPortfolio);
            $newEntry->setTitle($entry->getTitle());
            $newEntry->setShareheadId($entry->getShareheadId());
            $newEntry->setStartDate($entry->getStartDate());
            $entityManager->persist($newEntry);
        }
        $newPortfolio->setWatchlistEntries($newWatchlistEntries);

        $this->makeLogEntry('create demo portfolio', $newPortfolio, $entityManager);

        $entityManager->flush();

        return View::create($newPortfolio, Response::HTTP_CREATED);
    }


    /**
     * @param Portfolio $portfolio
     * @param EntityManagerInterface $entityManager
     * @return Currency[]
     */
    private function persistDefaultCurrencies(Portfolio $portfolio, EntityManagerInterface $entityManager): array
    {
        $newCurrencies = [];

        $currencies = [
            ['CHF', 1],
            ['EUR', 1.05],
            ['USD', 0.92],
            ['GBP', 1.25],
            ['DKK', 0.14],
            ['NOK', 0.11],
            ['SEK', 0.1],
            ['PLN', 0.25],
            ['CZK', 0.04],
            ['AUD', 0.67],
            ['HKD', 0.12],
            ['CNY', 0.14],
            ['CAD', 0.7],
            ['not defined', 1],
        ];
        foreach ($currencies as $currency) {
            $baseCurrency = new Currency();
            $baseCurrency->setPortfolioId($portfolio->getId());
            $baseCurrency->setName($currency[0]);
            $baseCurrency->setRate($currency[1]);
            $entityManager->persist($baseCurrency);
            $newCurrencies[] = $baseCurrency;
        }

        return $newCurrencies;
    }


    /**
     * @param Portfolio $portfolio
     * @param EntityManagerInterface $entityManager
     * @return void
     */
    private function persistDefaultLabels(Portfolio $portfolio, EntityManagerInterface $entityManager): void
    {
        $labels = ['Strategisch', 'Taktisch', 'Zykliker', 'Turnaround', 'Trading'];
        foreach ($labels as $label) {
            $baseLabel = new Label();
            $baseLabel->setPortfolioId($portfolio->getId());
            $baseLabel->setName($label);
            $entityManager->persist($baseLabel);
        }
    }


    /**
     * @param Portfolio $portfolio
     * @param EntityManagerInterface $entityManager
     * @return void
     */
    private function persistDefaultSectors(Portfolio $portfolio, EntityManagerInterface $entityManager): void
    {
        $sectors = ['Technologie', 'Industrie', 'Pharma', 'Rohstoffe', 'Banken', 'Versicherungen'];
        foreach ($sectors as $sector) {
            $baseSector = new Sector();
            $baseSector->setPortfolioId($portfolio->getId());
            $baseSector->setName($sector);
            $entityManager->persist($baseSector);
        }
    }


    /**
     * @param string $key
     * @param BalanceService $balanceService
     * @param EntityManagerInterface $entityManager
     * @return Portfolio
     */
    private function getPortfolioWithBalances(string $key, BalanceService $balanceService, EntityManagerInterface $entityManager): Portfolio
    {
        $portfolio = $entityManager->getRepository(Portfolio::class)->findOneBy(['hashKey' => $key]);
        if (null === $portfolio) {
            throw new AccessDeniedException();
        } else {
            $this->portfolio = $portfolio;
        }

        foreach ($portfolio->getBankAccounts() as $bankAccount) {
            foreach ($bankAccount->getPositions() as $position) {
                $balance = $balanceService->getBalanceForPosition($position);
                $position->setBalance($balance);
                if (null !== $position->getUnderlying()) {
                    $balance = $balanceService->getBalanceForPosition($position->getUnderlying());
                    $position->getUnderlying()->setBalance($balance);
                }
            }
        }

        return $portfolio;
    }

}
