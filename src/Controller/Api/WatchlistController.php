<?php

namespace App\Controller\Api;

use App\Entity\ShareheadShare;
use App\Entity\Watchlist;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class WatchlistController extends BaseController
{

    /**
     * @Rest\Get ("/watchlist", name="list_watchlist")
     * @param Request $request
     * @return View
     */
    public function listWatchlistEntries(Request $request): View
    {
        $portfolio = $this->getPortfolioByAuth($request);

        $entries = $this->getDoctrine()->getRepository(Watchlist::class)->findBy(['portfolio' => $portfolio], ['startDate' => 'DESC']);
        foreach($entries as $entry) {
            $shareheadShare = $this->getDoctrine()->getRepository(ShareheadShare::class)->findOneBy(['shareheadId' => $entry->getShareheadId()]);
            if (null !== $shareheadShare) {
                $entry->setTitle($shareheadShare->getName());
            }
        }

        return View::create($entries, Response::HTTP_CREATED);
    }


    /**
     * @Rest\Post("/watchlist", name="create_watchlist_entry")
     * @param Request $request
     * @return View
     */
    public function createWatchlistEntry(Request $request): View
    {
        $portfolio = $this->getPortfolioByAuth($request);

        $serializer = SerializerBuilder::create()->build();

        // todo: implement a better solution
        $content = json_decode($request->getContent());
        $shareheadId = $content->shareheadId;

        $watchlistEntry = new Watchlist();
        $watchlistEntry->setPortfolio($portfolio);
        $watchlistEntry->setStartDate(new \DateTime());
        $watchlistEntry->setShareheadId($shareheadId);

        $this->getDoctrine()->getManager()->persist($watchlistEntry);
        $this->getDoctrine()->getManager()->flush();

        $this->makeLogEntry('add watchlist-entry', $watchlistEntry);

        $watchlist = $this->getDoctrine()->getRepository(Watchlist::class)->findBy(['portfolio' => $portfolio], ['startDate' => 'DESC']);
        $sortArray = [];
        foreach($watchlist as $entry) {
            $shareheadShare = $this->getDoctrine()->getRepository(ShareheadShare::class)->findOneBy(['shareheadId' => $entry->getShareheadId()]);
            if (null !== $shareheadShare) {
                $entry->setTitle($shareheadShare->getName());
            }
        }

        return new View($watchlist, Response::HTTP_CREATED);
    }


    /**
     * @Rest\Delete("/watchlist/{shareheadId}", name="delete_watchlist_entry")
     * @param Request $request
     * @param int $shareheadId
     * @return View
     */
    public function deleteWatchlistEntry(Request $request, int $shareheadId): View
    {
        $portfolio = $this->getPortfolioByAuth($request);

        $watchlistEntry = $this->getDoctrine()->getRepository(Watchlist::class)->findOneBy(['portfolio' => $portfolio, 'shareheadId' => $shareheadId]);
        $this->getDoctrine()->getManager()->remove($watchlistEntry);
        $this->getDoctrine()->getManager()->flush();

        $this->makeLogEntry('remove watchlist-entry', $watchlistEntry);

        $watchlist = $this->getDoctrine()->getRepository(Watchlist::class)->findBy(['portfolio' => $portfolio], ['startDate' => 'DESC']);
        foreach($watchlist as $entry) {
            $shareheadShare = $this->getDoctrine()->getRepository(ShareheadShare::class)->findOneBy(['shareheadId' => $entry->getShareheadId()]);
            if (null !== $shareheadShare) {
                $entry->setTitle($shareheadShare->getName());
            }
        }

        return new View($watchlist, Response::HTTP_OK);
    }

}
