<?php

namespace App\Controller\Api;

use App\Entity\BankAccount;
use App\Entity\Portfolio;
use App\Helper\RandomizeHelper;
use App\Service\BalanceService;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;


class PortfolioController extends AbstractFOSRestController
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

        $this->getDoctrine()->getManager()->persist($portfolio);
        $this->getDoctrine()->getManager()->persist($bankAccount);
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
        }

        foreach($portfolio->getBankAccounts() as $bankAccount) {
            foreach($bankAccount->getPositions() as $position) {
                $balance = $balanceService->getBalanceForPosition($position);
                $position->setBalance($balance);
            }
        }

        return View::create($portfolio, Response::HTTP_OK);
    }

}
