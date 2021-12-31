<?php

namespace App\Controller\Api;

use App\Entity\Transaction;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class TransactionController extends AbstractFOSRestController
{

    /**
     * @Rest\Get ("/transaction/{transactionId}", name="get_transaction")
     * @param Request $request
     * @param int $transactionId
     * @return View
     */
    public function getPosition(Request $request, int $transactionId): View
    {

        $transaction = $this->getDoctrine()->getRepository(Transaction::class)->find($transactionId);

        return View::create($transaction, Response::HTTP_CREATED);
    }

}
