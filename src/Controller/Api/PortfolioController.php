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
use App\Entity\ShareheadShare;
use App\Entity\Strategy;
use App\Entity\Transaction;
use App\Entity\Watchlist;
use App\Helper\RandomizeHelper;
use App\Service\BalanceService;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;


class PortfolioController extends BaseController
{

    /**
     * @Rest\Post("/portfolio", name="create_portfolio")
     * @param Request $request
     * @return View
     */
    public function createPortfolio(Request $request): View
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

        $this->getDoctrine()->getManager()->persist($portfolio);
        $this->getDoctrine()->getManager()->persist($bankAccount);
        $this->getDoctrine()->getManager()->flush();

        $this->persistDefaultCurrencies($portfolio);
        $this->persistDefaultLabels($portfolio);
        $this->persistDefaultSectors($portfolio);

        $this->makeLogEntry('create new portfolio', $portfolio);

        $this->getDoctrine()->getManager()->flush();

        return View::create($portfolio, Response::HTTP_CREATED);
    }


    /**
     * @Rest\Post("/portfolio/restore", name="restore_portfolio")
     * @param Request $request
     * @param BalanceService $balanceService
     * @return View
     */
    public function restorePortfolio(Request $request, BalanceService $balanceService): View
    {
        $key = $request->headers->get('Authorization');

        $portfolio = $this->getPortfolioWithBalances($key, $balanceService);

        return View::create($portfolio, Response::HTTP_OK);
    }


    /**
     * @Rest\Post("/portfolio/time-warp", name="timewarp_portfolio")
     * @param Request $request
     * @param BalanceService $balanceService
     * @return View
     */
    public function timewarpedPortfolio(Request $request, BalanceService $balanceService): View
    {
        $key = $request->headers->get('Authorization');
        $body = json_decode($request->getContent(), false);
        $timeWarpDate = new \DateTime($body->date);

        $portfolio = $this->getPortfolioWithBalances($key, $balanceService);
        foreach($portfolio->getBankAccounts() as $account) {
            foreach($account->getPositions() as $position) {
                if ($position->getActiveFrom() > $timeWarpDate) {
                    $account->removePosition($position);
                }
            }
        }
        // todo: loop over positions and remove all activeFrom after the given date
        // todo: loop over positions and handle the activeUntil and isActive fields to make it real timewarped
        // todo: loop over positions->transactions and remove all after the given date

        return View::create($portfolio, Response::HTTP_OK);
    }


    /**
     * @Rest\Get("/portfolio/demo", name="create_demo_portfolio")
     * @param Request $request
     * @return View
     */
    public function createDemoPortfolio(Request $request): View
    {
        // todo: implement missing try catch loop since the randoms will not be unique...
        $randomUserName = RandomizeHelper::getRandomUserName();
        $randomHashKey = RandomizeHelper::getRandomHashKey();

        /** @var Portfolio $demoPortfolio */
        $demoPortfolio = $this->getDoctrine()->getRepository(Portfolio::class)->findOneBy(['id' => 169]);
        $demoCurrencies = $this->getDoctrine()->getRepository(Currency::class)->findBy(['portfolioId' => $demoPortfolio->getId()]);
        $demoPortfolio->setCurrencies($demoCurrencies);
        $demoShares = $this->getDoctrine()->getRepository(Share::class)->findBy(['portfolioId' => $demoPortfolio->getId()]);
        $demoPortfolio->setShares($demoShares);

//        $newPortfolio = clone $demoPortfolio;
        $newPortfolio = new Portfolio();
        $newPortfolio->setUserName($randomUserName);
        $newPortfolio->setHashKey($randomHashKey);
        $newPortfolio->setStartDate(new \DateTime());
        $this->getDoctrine()->getManager()->persist($newPortfolio);
        $this->getDoctrine()->getManager()->flush();
        $this->portfolio = $newPortfolio;

        $newCurrencies = $this->persistDefaultCurrencies($newPortfolio);
        $newPortfolio->setCurrencies($newCurrencies);

        /** @var Label[] $demoLabels */
        $demoLabels = $this->getDoctrine()->getRepository(Label::class)->findBy(['portfolioId' => $demoPortfolio->getId()]);
        $newLabels = [];
        foreach($demoLabels as $demoLabel) {
            $newLabel = clone $demoLabel;
            $newLabel->setPortfolioId($newPortfolio->getId());
            $this->getDoctrine()->getManager()->persist($newLabel);
            $newLabels[] = $newLabel;
        }
        $newPortfolio->setLabels($newLabels);

        /** @var Sector[] $demoSectors */
        $demoSectors = $this->getDoctrine()->getRepository(Sector::class)->findBy(['portfolioId' => $demoPortfolio->getId()]);
        $newSectors = [];
        foreach($demoSectors as $sector) {
            $newSector = clone $sector;
            $newSector->setPortfolioId($newPortfolio->getId());
            $this->getDoctrine()->getManager()->persist($newSector);
            $newSectors[] = $newSector;
        }
        $newPortfolio->setSectors($newSectors);

        /** @var Strategy[] $demoStrategies */
        $demoStrategies = $this->getDoctrine()->getRepository(Strategy::class)->findBy(['portfolioId' => $demoPortfolio->getId()]);
        $newStrategies = [];
        foreach($demoStrategies as $strategy) {
            $newStrategy = clone $strategy;
            $newStrategy->setPortfolioId($newPortfolio->getId());
            $this->getDoctrine()->getManager()->persist($newStrategy);
            $newStrategies[] = $newStrategy;
        }
        $newPortfolio->setSectors($newStrategies);

        $newShares = [];
        foreach($demoPortfolio->getShares() as $share) {
            $newShare = new Share();
            $newShare->setName($share->getName());
            $newShare->setIsin($share->getIsin());
            $newShare->setMarketplace($share->getMarketplace());
            $newShare->setBranche($share->getBranche());
            $newShare->setHeadquarter($share->getHeadquarter());
            $newShare->setValor($share->getValor());
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

            $this->getDoctrine()->getManager()->persist($newShare);
            $newShares[] = $newShare;
        }
        $newPortfolio->setShares($newShares);

        $accountNames = ['Konto A', 'Konto B'];
        $newAccounts = [];
        foreach($demoPortfolio->getBankAccounts() as $i => $account) {
            $newAccount = new BankAccount();
            $newAccount->setPortfolio($newPortfolio);
            $newAccount->setName($accountNames[$i]);
            $this->getDoctrine()->getManager()->persist($newAccount);

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
                $newPosition->setIsCash($position->isCash());
                $this->getDoctrine()->getManager()->persist($newPosition);

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
                    $newLogEntry = new PositionLog();
                    $newLogEntry->setPosition($newPosition);
                    $newLogEntry->setDate($logEntry->getDate());
                    $newLogEntry->setLog($logEntry->getLog());
                    $newLogEntry->setEmoticon($logEntry->getEmoticon());
                    $newLogEntries[] = $newLogEntry;
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
            $this->getDoctrine()->getManager()->persist($newEntry);
        }
        $newPortfolio->setWatchlistEntries($newWatchlistEntries);

        $this->makeLogEntry('create demo portfolio', $newPortfolio);

        $this->getDoctrine()->getManager()->flush();

        return View::create($newPortfolio, Response::HTTP_CREATED);
    }


    /**
     * @param Portfolio $portfolio
     * @return Currency[]
     */
    private function persistDefaultCurrencies(Portfolio $portfolio): array
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
            $this->getDoctrine()->getManager()->persist($baseCurrency);
            $newCurrencies[] = $baseCurrency;
        }

        return $newCurrencies;
    }


    /**
     * @param Portfolio $portfolio
     * @return Label[]
     */
    private function persistDefaultLabels(Portfolio $portfolio): array
    {
        $newLabels = [];

        $labels = ['Strategisch', 'Taktisch', 'Zykliker', 'Turnaround', 'Trading'];
        foreach ($labels as $label) {
            $baseLabel = new Label();
            $baseLabel->setPortfolioId($portfolio->getId());
            $baseLabel->setName($label);
            $this->getDoctrine()->getManager()->persist($baseLabel);
            $newLabels[] = $baseLabel;
        }

        return $newLabels;
    }


    /**
     * @param Portfolio $portfolio
     * @return Label[]
     */
    private function persistDefaultSectors(Portfolio $portfolio): array
    {
        $newSectors = [];

        $sectors = ['Technologie', 'Industrie', 'Pharma', 'Rohstoffe', 'Banken', 'Versicherungen'];
        foreach ($sectors as $sector) {
            $baseSector = new Sector();
            $baseSector->setPortfolioId($portfolio->getId());
            $baseSector->setName($sector);
            $this->getDoctrine()->getManager()->persist($baseSector);
            $newSectors[] = $baseSector;
        }

        return $newSectors;
    }


    /**
     * @param string $key
     * @param BalanceService $balanceService
     * @return Portfolio
     */
    private function getPortfolioWithBalances(string $key, BalanceService $balanceService): Portfolio
    {
        $portfolio = $this->getDoctrine()->getRepository(Portfolio::class)->findOneBy(['hashKey' => $key]);
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
        foreach ($portfolio->getWatchlistEntries() as $entry) {
            $shareheadShare = $this->getDoctrine()->getRepository(ShareheadShare::class)->findOneBy(['shareheadId' => $entry->getShareheadId()]);
            if (null !== $shareheadShare) {
                $entry->setTitle($shareheadShare->getName());
            }
        }

        return $portfolio;
    }

}
