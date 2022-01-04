<?php

namespace App\Controller\Api;

use App\Entity\Portfolio;
use App\Entity\Transaction;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\Finder\Exception\AccessDeniedException;
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


    /**
     * @Rest\Post("/transaction", name="create_transaction")
     * @param Request $request
     * @return View
     * @throws \Exception
     */
    public function createTransaction(Request $request): View
    {
        $key = $request->headers->get('Authorization');
        $portfolio = $this->getDoctrine()->getRepository(Portfolio::class)->findOneBy(['hashKey' => $key]);
        if (null === $portfolio) {
            throw new \Exception(AccessDeniedException::class);
        }

        $serializer = SerializerBuilder::create()->build();
        /** @var Transaction $transaction */
        $transaction = $serializer->deserialize($request->getContent(), Transaction::class, 'json');

        $position = $portfolio->getPositionById($transaction->getPosition()->getId());
        if (null === $position) {
            throw new \Exception(AccessDeniedException::class);
        } else {
            $transaction->setPosition($position);
        }

        $this->getDoctrine()->getManager()->persist($transaction);
        $this->getDoctrine()->getManager()->flush();

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
        $key = $request->headers->get('Authorization');
        $portfolio = $this->getDoctrine()->getRepository(Portfolio::class)->findOneBy(['hashKey' => $key]);
        if (null === $portfolio) {
            throw new \Exception(AccessDeniedException::class);
        }

        $existingTransaction = $this->getDoctrine()->getRepository(Transaction::class)->find($transactionId);
        if (null === $existingTransaction) {
            throw new \Exception(AccessDeniedException::class);
        }

        $serializer = SerializerBuilder::create()->build();
        /** @var Transaction $updatedTransaction */
        $updatedTransaction = $serializer->deserialize($request->getContent(), Transaction::class, 'json');

        $position = $portfolio->getPositionById($updatedTransaction->getPosition()->getId());
        if (null === $position) {
            throw new \Exception(AccessDeniedException::class);
        } else {
            $updatedTransaction->setPosition($position);
        }

        $existingTransaction->setDate($updatedTransaction->getDate());
        $existingTransaction->setTitle($updatedTransaction->getTitle());
        $existingTransaction->setQuantity($updatedTransaction->getQuantity());
        $existingTransaction->setRate($updatedTransaction->getRate());
        $existingTransaction->setFee($updatedTransaction->getFee());

        $this->getDoctrine()->getManager()->persist($existingTransaction);
        $this->getDoctrine()->getManager()->flush();

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
        $transaction = $this->getDoctrine()->getRepository(Transaction::class)->find($transactionId);
        $this->getDoctrine()->getManager()->remove($transaction);
        $this->getDoctrine()->getManager()->flush();

        return new View("Transaction Delete Successfully", Response::HTTP_OK);
    }

}
