<?php

namespace App\Controller\Api;

use App\Entity\LogEntry;
use App\Entity\Portfolio;
use App\Entity\Position;
use App\Entity\Transaction;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class TransactionController extends BaseController
{

    /**
     * @Rest\Get ("/transaction/{transactionId}", name="get_transaction")
     * @param Request $request
     * @param int $transactionId
     * @return View
     */
    public function getTransaction(Request $request, int $transactionId): View
    {
        $portfolio = $this->getPortfolio($request);

        $transaction = $this->getDoctrine()->getRepository(Transaction::class)->find($transactionId);
        $transaction->setPosition(null);

        return View::create($transaction, Response::HTTP_CREATED);
    }


    /**
     * @Rest\Post("/transaction", name="create_transaction")
     * @param Request $request
     * @return View
     * @throws \Exception
     */
    public function createTransaction(Request $request): View
    {
        $portfolio = $this->getPortfolio($request);

        $serializer = SerializerBuilder::create()->build();
        /** @var Transaction $transaction */
        $transaction = $serializer->deserialize($request->getContent(), Transaction::class, 'json');

        $position = $portfolio->getPositionById($transaction->getPosition()->getId());
        if (null === $position) {
            throw new AccessDeniedException();
        } else {
            $transaction->setPosition($position);
        }

        if ($transaction->getDate() < $position->getActiveFrom()) {
            $position->setActiveFrom($transaction->getDate());
        }

        $currency = $portfolio->getCurrencyByName($transaction->getCurrency()->getName());
        $transaction->setCurrency($currency);

        $cashPosition = $position->getBankAccount()->getCashPositionByCurrency($currency);
        if (null === $cashPosition) {
            $cashPosition = new Position();
            $cashPosition->setBankAccount($position->getBankAccount());
            $cashPosition->setActiveFrom($transaction->getDate());
            $cashPosition->setCurrency($currency);
            $cashPosition->setIsCash(true);
            $this->getDoctrine()->getManager()->persist($cashPosition);

            $this->makeLogEntry('forced creation of new cash-position', $cashPosition);
        }
        if (false === $transaction->getPosition()->isCash()) {
            $cashTransaction = new Transaction();
            $cashTransaction->setPosition($cashPosition);
            $cashTransaction->setCurrency($cashPosition->getCurrency());
            $cashTransaction->setQuantity(1);
            $cashTransaction->setDate($transaction->getDate());
            $cashTransaction->setTitle($transaction->getTitle());
            $cashTransaction->setRate($transaction->calculateCashValueNet());
            $this->getDoctrine()->getManager()->persist($cashTransaction);
            $this->makeLogEntry('forced new cash-transaction', $cashTransaction);
        }

        $this->getDoctrine()->getManager()->persist($position);
        $this->getDoctrine()->getManager()->persist($transaction);

        $this->makeLogEntry('create new transaction', $transaction);

        $this->getDoctrine()->getManager()->flush();

        $transaction->setPosition(null);
        return View::create($transaction, Response::HTTP_OK);
    }


    /**
     * @Rest\Put("/transaction/{transactionId}", name="update_transaction")
     * @param Request $request
     * @param int $transactionId
     * @return View
     * @throws \Exception
     */
    public function updateTransaction(Request $request, int $transactionId): View
    {
        $portfolio = $this->getPortfolio($request);

        $existingTransaction = $this->getDoctrine()->getRepository(Transaction::class)->find($transactionId);
        if (null === $existingTransaction) {
            throw new AccessDeniedException();
        }

        $serializer = SerializerBuilder::create()->build();
        /** @var Transaction $updatedTransaction */
        $updatedTransaction = $serializer->deserialize($request->getContent(), Transaction::class, 'json');

        $position = $portfolio->getPositionById($updatedTransaction->getPosition()->getId());
        if (null === $position) {
            throw new AccessDeniedException();
        } else {
            $updatedTransaction->setPosition($position);
        }

        $existingTransaction->setDate($updatedTransaction->getDate());
        $existingTransaction->setTitle($updatedTransaction->getTitle());
        $existingTransaction->setQuantity($updatedTransaction->getQuantity());
        $existingTransaction->setRate($updatedTransaction->getRate());
        $existingTransaction->setFee($updatedTransaction->getFee());

        $currency = $portfolio->getCurrencyByName($updatedTransaction->getCurrency()->getName());
        $existingTransaction->setCurrency($currency);

        if ($updatedTransaction->getDate() < $position->getActiveFrom()) {
            $position->setActiveFrom($updatedTransaction->getDate());
        }

        $this->getDoctrine()->getManager()->persist($position);
        $this->getDoctrine()->getManager()->persist($existingTransaction);

        $this->makeLogEntry('update transaction', $existingTransaction);

        $this->getDoctrine()->getManager()->flush();

        $updatedTransaction->setPosition(null);
        return View::create($updatedTransaction, Response::HTTP_OK);
    }


    /**
     * @Rest\Delete("/transaction/{transactionId}", name="delete_transaction")
     * @param Request $request
     * @param int $transactionId
     * @return View
     */
    public function deleteTransaction(Request $request, int $transactionId): View
    {
        $portfolio = $this->getPortfolio($request);

        $transaction = $this->getDoctrine()->getRepository(Transaction::class)->find($transactionId);
        $this->getDoctrine()->getManager()->remove($transaction);
        $this->getDoctrine()->getManager()->flush();

        return new View("Transaction Delete Successfully", Response::HTTP_OK);
    }

}
