<?php

namespace App\Controller\Api;

use App\Entity\Watchlist;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\View\View;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class WatchlistController extends BaseController
{

    /**
     * @Rest\Get ("/watchlist", name="list_watchlist")
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return View
     */
    public function listWatchlistEntries(Request $request, EntityManagerInterface $entityManager): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

        $entries = $entityManager->getRepository(Watchlist::class)->findBy(['portfolio' => $portfolio], ['startDate' => 'DESC']);

        return View::create($entries, Response::HTTP_CREATED);
    }


    /**
     * @Rest\Post("/watchlist", name="create_watchlist_entry")
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return View
     */
    public function createWatchlistEntry(Request $request, EntityManagerInterface $entityManager): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

        $serializer = SerializerBuilder::create()->build();

        // todo: implement a better solution
        $content = json_decode($request->getContent());
        $shareheadId = $content->shareheadId;

        $watchlistEntry = new Watchlist();
        $watchlistEntry->setPortfolio($portfolio);
        $watchlistEntry->setStartDate(new \DateTime());
        $watchlistEntry->setShareheadId($shareheadId);

        $entityManager->persist($watchlistEntry);
        $entityManager->flush();

        $this->makeLogEntry('add watchlist-entry', $watchlistEntry, $entityManager);

        $watchlist = $entityManager->getRepository(Watchlist::class)->findBy(['portfolio' => $portfolio], ['startDate' => 'DESC']);

        return new View($watchlist, Response::HTTP_CREATED);
    }


    /**
     * @Rest\Delete("/watchlist/{shareheadId}", name="delete_watchlist_entry")
     * @param Request $request
     * @param int $shareheadId
     * @param EntityManagerInterface $entityManager
     * @return View
     */
    public function deleteWatchlistEntry(Request $request, int $shareheadId, EntityManagerInterface $entityManager): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

        $watchlistEntry = $entityManager->getRepository(Watchlist::class)->findOneBy(['portfolio' => $portfolio, 'shareheadId' => $shareheadId]);
        $entityManager->remove($watchlistEntry);
        $entityManager->flush();

        $this->makeLogEntry('remove watchlist-entry', $watchlistEntry, $entityManager);

        $watchlist = $entityManager->getRepository(Watchlist::class)->findBy(['portfolio' => $portfolio], ['startDate' => 'DESC']);

        return new View($watchlist, Response::HTTP_OK);
    }

}
