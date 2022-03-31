<?php

namespace App\Controller\Api;

use App\Entity\BankAccount;
use App\Entity\Currency;
use App\Entity\LogEntry;
use App\Entity\Portfolio;
use App\Entity\Position;
use App\Entity\Share;
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

        $this->persistDefaultCurrencies($portfolio);

        $this->makeLogEntry('create new portfolio', $portfolio);

        $this->getDoctrine()->getManager()->flush();

        return View::create($portfolio, Response::HTTP_CREATED);
    }


    /**
     * @Rest\Post("/portfolio/restore", name="restore_portfolio")
     * @param Request $request
     * @return View
     */
    public function restorePortfolio(Request $request, BalanceService $balanceService): View
    {
        // todo: implement a better solution
        $content = json_decode($request->getContent());

        $portfolio = $this->getDoctrine()->getRepository(Portfolio::class)->findOneBy(['hashKey' => $content->hashKey]);
        if (null === $portfolio) {
            throw new AccessDeniedException();
        } else {
            $this->portfolio = $portfolio;
        }

        foreach($portfolio->getBankAccounts() as $bankAccount) {
            foreach($bankAccount->getPositions() as $position) {
                $balance = $balanceService->getBalanceForPosition($position);
                $position->setBalance($balance);
            }
        }

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

        $demoPortfolio = $this->getDoctrine()->getRepository(Portfolio::class)->findOneBy(['id' => 164]);
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
            $this->getDoctrine()->getManager()->persist($newShare);
            $newShares[] = $newShare;
        }
        $newPortfolio->setShares($newShares);

        $newAccounts = [];
        foreach($demoPortfolio->getBankAccounts() as $account) {
            $newAccount = new BankAccount();
            $newAccount->setPortfolio($newPortfolio);
            $newAccount->setName($account->getName());
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
                $newPositions[] = $newPosition;
            }
            $newAccount->setPositions($newPositions);

            $newAccounts[] = $newAccount;
        }
        $newPortfolio->setBankAccounts($newAccounts);

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

}
