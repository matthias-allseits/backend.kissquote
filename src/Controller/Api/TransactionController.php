<?php

namespace App\Controller\Api;

use App\Entity\Position;
use App\Entity\Transaction;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\View\View;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;


class TransactionController extends BaseController
{

    #[Route('/api/position/{positionId}/transaction/{transactionId}', name: 'get_transaction', methods: ['GET', 'OPTIONS'])]
    public function getTransaction(Request $request, int $positionId, int $transactionId, EntityManagerInterface $entityManager): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

        $position = $portfolio->getPositionById($positionId);
        if (null === $position) {
            throw new AccessDeniedException();
        }

        $transaction = $entityManager->getRepository(Transaction::class)->find($transactionId);

        return View::create($transaction, Response::HTTP_CREATED);
    }


    #[Route('/api/position/{positionId}/transaction', name: 'create_transaction', methods: ['POST', 'OPTIONS'])]
    public function createTransaction(Request $request, int $positionId, EntityManagerInterface $entityManager, SerializerInterface $serializer): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

        /** @var Transaction $transaction */
        $transaction = $serializer->deserialize($request->getContent(), Transaction::class, 'json');

        $position = $portfolio->getPositionById($positionId);
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
            $entityManager->persist($cashPosition);

            $this->makeLogEntry('forced creation of new cash-position', $cashPosition, $entityManager);
        }
        if (false === $transaction->getPosition()->isCash()) {
            $cashTransaction = new Transaction();
            $cashTransaction->setPosition($cashPosition);
            $cashTransaction->setCurrency($cashPosition->getCurrency());
            $cashTransaction->setQuantity(1);
            $cashTransaction->setDate($transaction->getDate());
            $cashTransaction->setTitle($transaction->getTitle());
            $cashTransaction->setRate($transaction->calculateCashValueNet());
            $entityManager->persist($cashTransaction);
            $this->makeLogEntry('forced new cash-transaction', $cashTransaction, $entityManager);
        }

        $entityManager->persist($position);
        $entityManager->persist($transaction);

        $this->makeLogEntry('create new transaction', $transaction, $entityManager);

        $entityManager->flush();

        return View::create($transaction, Response::HTTP_OK);
    }


    #[Route('/api/position/{positionId}/transaction/{transactionId}', name: 'update_transaction', methods: ['PUT', 'OPTIONS'])]
    public function updateTransaction(Request $request, int $positionId, int $transactionId, EntityManagerInterface $entityManager, SerializerInterface $serializer): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

        $existingTransaction = $entityManager->getRepository(Transaction::class)->find($transactionId);
        if (null === $existingTransaction) {
            throw new AccessDeniedException();
        }

        /** @var Transaction $updatedTransaction */
        $updatedTransaction = $serializer->deserialize($request->getContent(), Transaction::class, 'json');

        $position = $portfolio->getPositionById($positionId);
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

        $entityManager->persist($position);
        $entityManager->persist($existingTransaction);

        $this->makeLogEntry('update transaction', $existingTransaction, $entityManager);

        $entityManager->flush();

        return View::create($updatedTransaction, Response::HTTP_OK);
    }


    #[Route('/api/position/{positionId}/transaction/{transactionId}', name: 'delete_transaction', methods: ['DELETE', 'OPTIONS'])]
    public function deleteTransaction(Request $request, int $transactionId, EntityManagerInterface $entityManager): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

        $transaction = $entityManager->getRepository(Transaction::class)->find($transactionId);
        $entityManager->remove($transaction);
        $entityManager->flush();

        return new View("Transaction Delete Successfully", Response::HTTP_OK);
    }

}
