<?php

namespace App\Controller\Api;

use App\Entity\Position;
use App\Entity\Sector;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;


class SectorController extends BaseController
{

    /**
     * @Rest\Get ("/sector", name="list_sectors")
     * @param Request $request
     * @return View
     */
    public function listSectors(Request $request): View
    {
        $portfolio = $this->getPortfolio($request);

        $sectors = $portfolio->getSectors();

        return View::create($sectors, Response::HTTP_CREATED);
    }


    /**
     * @Rest\Post("/sector", name="create_sector")
     * @param Request $request
     * @return View
     */
    public function createSector(Request $request): View
    {
        $portfolio = $this->getPortfolio($request);

        $serializer = SerializerBuilder::create()->build();
        $content = json_decode($request->getContent());
        /** @var Sector $postedSector */
        $postedSector = $serializer->deserialize(json_encode($content), Sector::class, 'json');

        $existingSector = $portfolio->getSectorByName($postedSector->getName());
        if (null === $existingSector) {
            $postedSector->setPortfolioId($portfolio->getId());

            $this->getDoctrine()->getManager()->persist($postedSector);
            $this->getDoctrine()->getManager()->flush();
        }

        return new View("Sector Creation Successfully", Response::HTTP_OK);
    }


    /**
     * @Rest\Put("/sector/{sectorId}", name="update_sector")
     * @param Request $request
     * @param int $sectorId
     * @return View
     */
    public function updateSector(Request $request, int $sectorId): View
    {
        $portfolio = $this->getPortfolio($request);

        $serializer = SerializerBuilder::create()->build();
        $content = json_decode($request->getContent());
        /** @var Sector $puttedSector */
        $puttedSector = $serializer->deserialize(json_encode($content), Sector::class, 'json');

        $existingSector = $portfolio->getSectorById($puttedSector->getId());

        if (null !== $existingSector && $puttedSector->getId() == $existingSector->getId()) {
            $existingSector->setName($puttedSector->getName());

            $this->getDoctrine()->getManager()->persist($existingSector);

            $this->makeLogEntry('update sector', $existingSector->getName());

            $this->getDoctrine()->getManager()->flush();

            return new View("Sector Update Successfully", Response::HTTP_OK);
        } else {

            throw new AccessDeniedException();
        }

    }


    /**
     * @Rest\Delete("/sector/{sectorId}", name="delete_sector")
     * @param Request $request
     * @param int $sectorId
     * @return View
     */
    public function deleteSector(Request $request, int $sectorId): View
    {
        $portfolio = $this->getPortfolio($request);

        $sector = $this->getDoctrine()->getRepository(Sector::class)->find($sectorId);

        $affectedPositions = $this->getDoctrine()->getRepository(Position::class)->findBy(['sector' => $sectorId]);
        foreach($affectedPositions as $position) {
            $position->setSector(null);
            $this->getDoctrine()->getManager()->persist($position);
            $this->addPositionLogEntry('Entferne gelöschten Sektor: ' . $sector->getName(), $position);
        }
        $this->getDoctrine()->getManager()->remove($sector);

        $this->makeLogEntry('delete sector', $sector);

        $this->getDoctrine()->getManager()->flush();

        return new View("Sector Delete Successfully", Response::HTTP_OK);
    }

}
