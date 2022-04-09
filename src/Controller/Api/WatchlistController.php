<?php

namespace App\Controller\Api;

use App\Entity\Watchlist;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class WatchlistController extends BaseController
{


    /**
     * @Rest\Post("/watchlist/{shareheadId}", name="create_watchlist_entry")
     * @param Request $request
     * @param int $shareheadId
     * @return View
     */
    public function createWatchlistEntry(Request $request, int $shareheadId): View
    {
        $portfolio = $this->getPortfolio($request);

        $serializer = SerializerBuilder::create()->build();

        $shareheadId = $request->request->get('shareheadId');

        $watchlistEntry = new Watchlist();
        $watchlistEntry->setPortfolio($portfolio);
        $watchlistEntry->setStartDate(new \DateTime());
        $watchlistEntry->setStartDate($shareheadId);

        $this->getDoctrine()->getManager()->persist($watchlistEntry);
        $this->getDoctrine()->getManager()->flush();

        $this->makeLogEntry('add watchlist-entry', $watchlistEntry);

        return new View("Watchlist Entry Successfully", Response::HTTP_OK);
    }


    /**
     * @Rest\Delete("/watchlist/{shareheadId}", name="delete_watchlist_entry")
     * @param Request $request
     * @param int $shareheadId
     * @return View
     */
    public function deleteWatchlistEntry(Request $request, int $shareheadId): View
    {
        $portfolio = $this->getPortfolio($request);

        $watchlistEntry = $this->getDoctrine()->getRepository(Watchlist::class)->findOneBy(['portfolio' => $portfolio, 'shareheadId' => $shareheadId]);
        $this->getDoctrine()->getManager()->remove($watchlistEntry);
        $this->getDoctrine()->getManager()->flush();

        $this->makeLogEntry('remove watchlist-entry', $watchlistEntry);

        return new View("Bank-Account Delete Successfully", Response::HTTP_OK);
    }

}
