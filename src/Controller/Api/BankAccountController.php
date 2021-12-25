<?php

namespace App\Controller\Api;

use App\Entity\BankAccount;
use App\Entity\Portfolio;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class BankAccountController extends AbstractFOSRestController
{

    /**
     * @Rest\Post("/bank-account", name="create_account")
     * @param Request $request
     * @return View
     */
    public function createBankAccount(Request $request): View
    {
        // todo: implement a better solution
        $content = json_decode($request->getContent());
        $key = $request->headers->get('Authorization');

        $bankAccount = null;
        if (isset($content->name) && strlen($key) > 0) {
            $portfolio = $this->getDoctrine()->getRepository(Portfolio::class)->findOneBy(['hashKey' => $key]);

            if (null !== $portfolio) {
                $bankAccount = new BankAccount();
                $bankAccount->setName($content->name);
                $bankAccount->setPortfolio($portfolio);
                $this->getDoctrine()->getManager()->persist($bankAccount);
                $this->getDoctrine()->getManager()->flush();
            }
        }

        return View::create($bankAccount, Response::HTTP_OK);
    }


    /**
     * @Rest\Put("/bank-account/{accountId}", name="update_account")
     * @param Request $request
     * @param int $accountId
     * @return View
     */
    public function updateBankAccount(Request $request, int $accountId): View
    {
        // todo: implement a better solution
        $content = json_decode($request->getContent());

        /** @var BankAccount $bankAccount */
        $bankAccount = $this->getDoctrine()->getRepository(BankAccount::class)->find($accountId);
        if (null !== $bankAccount) {
            $bankAccount->setName($content->name);
            $this->getDoctrine()->getManager()->flush();
        } else {
            return View::create(null, Response::HTTP_NOT_FOUND);
        }

        return View::create($bankAccount, Response::HTTP_OK);
    }

}
