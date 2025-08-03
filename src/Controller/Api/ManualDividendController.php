<?php

namespace App\Controller\Api;

use App\Entity\ManualDividend;
use App\Entity\Share;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;


class ManualDividendController extends BaseController
{

    #[Route('/api/manualDividend', name: 'create_manual_dividend', methods: ['POST', 'OPTIONS'])]
    public function createManualDividend(Request $request, EntityManagerInterface $entityManager): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

        $data = json_decode($request->getContent());

        $share = $entityManager->getRepository(Share::class)->findOneBy(['portfolioId' => $portfolio->getId(), 'id' => $data->share->id]);

        $postedDividend = new ManualDividend();
        $postedDividend->setYear($data->year);
        $postedDividend->setAmount($data->amount);
        $postedDividend->setShare($share);

        $entityManager->persist($postedDividend);
        $entityManager->flush();

        $this->makeLogEntry('add manual-dividend', $postedDividend, $entityManager);

        return new View($postedDividend, Response::HTTP_CREATED);
    }


    #[Route('/api/manualDividend/{dividendId}', name: 'update_manual_dividend', methods: ['PUT', 'OPTIONS'])]
    public function updateManualDividend(Request $request, int $dividendId, EntityManagerInterface $entityManager): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

        $data = json_decode($request->getContent());

        /** @var ManualDividend $existingDividend */
        $existingDividend = $portfolio->getManualDividendById($dividendId);

        if (null !== $existingDividend && $dividendId == $existingDividend->getId()) {
            $existingDividend->setAmount($data->amount);
            $existingDividend->setYear($data->year);

            $entityManager->persist($existingDividend);

            $this->makeLogEntry('update manual-dividend', $existingDividend, $entityManager);

            $entityManager->flush();

            return new View($existingDividend, Response::HTTP_OK);
        } else {

            throw new AccessDeniedException();
        }

    }


    #[Route('/api/manualDividend/{dividendId}', name: 'delete_manual_dividend', methods: ['DELETE', 'OPTIONS'])]
    public function deleteManualDividend(Request $request, int $dividendId, EntityManagerInterface $entityManager): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

        $manualDividend = $entityManager->getRepository(ManualDividend::class)->find($dividendId);
        if ($manualDividend->getShare()->getPortfolioId() == $portfolio->getId()) {
            $entityManager->remove($manualDividend);
            $entityManager->flush();

            $this->makeLogEntry('remove manual-dividend', $manualDividend, $entityManager);
        }

        return new View(null, Response::HTTP_OK);
    }

}
