<?php

namespace App\Controller\Api;

use App\Entity\BankAccount;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class BankAccountController extends BaseController
{

    /**
     * @Rest\Post("/bank-account", name="create_account")
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return View
     */
    public function createBankAccount(Request $request, EntityManagerInterface $entityManager): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

        // todo: implement a better solution
        $content = json_decode($request->getContent());

        $bankAccount = null;
        if (isset($content->name)) {
            $bankAccount = new BankAccount();
            $bankAccount->setName($content->name);
            $bankAccount->setPortfolio($portfolio);
            $entityManager->persist($bankAccount);

            $this->makeLogEntry('create new bank-account', $bankAccount, $entityManager);

            $entityManager->flush();
        }

        return View::create($bankAccount, Response::HTTP_OK);
    }


    /**
     * @Rest\Put("/bank-account/{accountId}", name="update_account")
     * @param Request $request
     * @param int $accountId
     * @param EntityManagerInterface $entityManager
     * @return View
     */
    public function updateBankAccount(Request $request, int $accountId, EntityManagerInterface $entityManager): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

        // todo: implement a better solution
        $content = json_decode($request->getContent());

        /** @var BankAccount $bankAccount */
        $bankAccount = $entityManager->getRepository(BankAccount::class)->find($accountId);
        if (null !== $bankAccount) {
            $oldName = $bankAccount->getName();
            $this->portfolio = $bankAccount->getPortfolio();
            $bankAccount->setName($content->name);

            $this->makeLogEntry('update bank-account', $oldName . ' -> ' . $content->name, $entityManager);

            $entityManager->flush();
        } else {
            return View::create(null, Response::HTTP_NOT_FOUND);
        }

        return View::create($bankAccount, Response::HTTP_OK);
    }


    /**
     * @Rest\Delete("/bank-account/{accountId}", name="delete_account")
     * @param Request $request
     * @param int $accountId
     * @param EntityManagerInterface $entityManager
     * @return View
     */
    public function deleteBankAccount(Request $request, int $accountId, EntityManagerInterface $entityManager): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

        $bankAccount = $entityManager->getRepository(BankAccount::class)->find($accountId);
        $entityManager->remove($bankAccount);

        $this->makeLogEntry('delete bank-account', $bankAccount, $entityManager);

        $entityManager->flush();

        return new View("Bank-Account Delete Successfully", Response::HTTP_OK);
    }

}
