<?php

namespace App\Controller\Api;

use App\Entity\ManualDividend;
use App\Entity\Share;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;


class ManualDividendController extends BaseController
{

    /**
     * @Rest\Post("/manualDividend", name="create_manual_dividend")
     * @param Request $request
     * @return View
     */
    public function createManualDividend(Request $request): View
    {
        $portfolio = $this->getPortfolioByAuth($request);

        $serializer = SerializerBuilder::create()->build();

        $serializer = SerializerBuilder::create()->build();
        $content = json_decode($request->getContent());
        /** @var ManualDividend $postedDividend */
        $postedDividend = $serializer->deserialize(json_encode($content), ManualDividend::class, 'json');

        $share = $this->getDoctrine()->getRepository(Share::class)->findOneBy(['portfolioId' => $portfolio->getId(), 'id' => $postedDividend->getShare()->getId()]);
        $postedDividend->setShare($share);

        $this->getDoctrine()->getManager()->persist($postedDividend);
        $this->getDoctrine()->getManager()->flush();

        $this->makeLogEntry('add manual-dividend', $postedDividend);

        return new View($postedDividend, Response::HTTP_CREATED);
    }


    /**
     * @Rest\Put("/manualDividend/{dividendId}", name="update_manual_dividend")
     * @param Request $request
     * @param int $dividendId
     * @return View
     */
    public function updateManualDividend(Request $request, int $dividendId): View
    {
        $portfolio = $this->getPortfolioByAuth($request);
        $shares = $this->getDoctrine()->getRepository(Share::class)->findBy(['portfolioId' => $portfolio->getId()]);
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

            $this->getDoctrine()->getManager()->persist($existingDividend);

            $this->makeLogEntry('update manual-dividend', $puttedDividend);

            $this->getDoctrine()->getManager()->flush();

            return new View($existingDividend, Response::HTTP_OK);
        } else {

            throw new AccessDeniedException();
        }

    }


    /**
     * @Rest\Delete("/manualDividend/{dividendId}", name="delete_manual_dividend")
     * @param Request $request
     * @param int $dividendId
     * @return View
     */
    public function deleteManualDividend(Request $request, int $dividendId): View
    {
        $portfolio = $this->getPortfolioByAuth($request);

        $manualDividend = $this->getDoctrine()->getRepository(ManualDividend::class)->find($dividendId);
        if ($manualDividend->getShare()->getPortfolioId() == $portfolio->getId()) {
            $this->getDoctrine()->getManager()->remove($manualDividend);
            $this->getDoctrine()->getManager()->flush();

            $this->makeLogEntry('remove manual-dividend', $manualDividend);
        }

        return new View(null, Response::HTTP_OK);
    }

}
