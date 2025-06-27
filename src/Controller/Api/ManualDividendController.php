<?php

namespace App\Controller\Api;

use App\Entity\ManualDividend;
use App\Entity\Share;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\View\View;
use JMS\Serializer\SerializerBuilder;
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

        $share = $entityManager->getRepository(Share::class)->findOneBy(['portfolioId' => $portfolio->getId(), 'id' => $data->shareId]);

        $postedDividend = new ManualDividend();
        $postedDividend->setYear($data->year);
        $postedDividend->setAmount($data->amount);
        $postedDividend->setShare($share);

        $entityManager->persist($postedDividend);
        $entityManager->flush();

        $this->makeLogEntry('add manual-dividend', $postedDividend, $entityManager);

        return new View($postedDividend, Response::HTTP_CREATED);
    }


    /**
     * @Rest\Put("/manualDividend/{dividendId}", name="update_manual_dividend")
     * @param Request $request
     * @param int $dividendId
     * @param EntityManagerInterface $entityManager
     * @return View
     */
    public function updateManualDividend(Request $request, int $dividendId, EntityManagerInterface $entityManager): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);
        $shares = $entityManager->getRepository(Share::class)->findBy(['portfolioId' => $portfolio->getId()]);
        $portfolio->setShares($shares);

        $serializer = SerializerBuilder::create()->build();
        $content = json_decode($request->getContent());

        /** @var ManualDividend $puttedDividend */
        $puttedDividend = $serializer->deserialize(json_encode($content), ManualDividend::class, 'json');

        /** @var ManualDividend $existingDividend */
        $existingDividend = $portfolio->getManualDividendById($dividendId);

        if (null !== $existingDividend && $puttedDividend->getId() == $existingDividend->getId()) {
            $existingDividend->setAmount($puttedDividend->getAmount());
            $existingDividend->setYear($puttedDividend->getYear());

            $entityManager->persist($existingDividend);

            $this->makeLogEntry('update manual-dividend', $puttedDividend, $entityManager);

            $entityManager->flush();

            return new View($existingDividend, Response::HTTP_OK);
        } else {

            throw new AccessDeniedException();
        }

    }


    /**
     * @Rest\Delete("/manualDividend/{dividendId}", name="delete_manual_dividend")
     * @param Request $request
     * @param int $dividendId
     * @param EntityManagerInterface $entityManager
     * @return View
     */
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
