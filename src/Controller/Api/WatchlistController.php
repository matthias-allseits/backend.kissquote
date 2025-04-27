<?php

namespace App\Controller\Api;

use App\Entity\Watchlist;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\View\View;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class WatchlistController extends BaseController
{

    #[Route('/watchlist', name: 'list_watchlist', methods: ['GET'])]
    public function listWatchlistEntries(Request $request, EntityManagerInterface $entityManager): View
    {
        $portfolio = $this->getPortfolioByAuth($request);

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
        $portfolio = $this->getPortfolioByAuth($request);

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

        $this->makeLogEntry('add watchlist-entry', $watchlistEntry);

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
        $portfolio = $this->getPortfolioByAuth($request);

        $watchlistEntry = $entityManager->getRepository(Watchlist::class)->findOneBy(['portfolio' => $portfolio, 'shareheadId' => $shareheadId]);
        $entityManager->remove($watchlistEntry);
        $entityManager->flush();

        $this->makeLogEntry('remove watchlist-entry', $watchlistEntry);

        $watchlist = $entityManager->getRepository(Watchlist::class)->findBy(['portfolio' => $portfolio], ['startDate' => 'DESC']);

        return new View($watchlist, Response::HTTP_OK);
    }

}
