<?php

namespace App\Controller\Api;

use App\Entity\Position;
use App\Entity\Sector;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\View\View;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;


class SectorController extends BaseController
{

    #[Route('/api/sector', name: 'list_sectors', methods: ['GET', 'OPTIONS'])]
    public function listSectors(Request $request, EntityManagerInterface $entityManager): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

        $sectors = $portfolio->getSectors();

        return View::create($sectors, Response::HTTP_CREATED);
    }


    /**
     * @Rest\Post("/sector", name="create_sector")
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return View
     */
    public function createSector(Request $request, EntityManagerInterface $entityManager): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

        $serializer = SerializerBuilder::create()->build();
        $content = json_decode($request->getContent());
        /** @var Sector $postedSector */
        $postedSector = $serializer->deserialize(json_encode($content), Sector::class, 'json');

        $existingSector = $portfolio->getSectorByName($postedSector->getName());
        if (null === $existingSector) {
            $postedSector->setPortfolioId($portfolio->getId());

            $entityManager->persist($postedSector);
            $entityManager->flush();
        }

        return new View("Sector Creation Successfully", Response::HTTP_OK);
    }


    /**
     * @Rest\Put("/sector/{sectorId}", name="update_sector")
     * @param Request $request
     * @param int $sectorId
     * @param EntityManagerInterface $entityManager
     * @return View
     */
    public function updateSector(Request $request, int $sectorId, EntityManagerInterface $entityManager): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

        $serializer = SerializerBuilder::create()->build();
        $content = json_decode($request->getContent());
        /** @var Sector $puttedSector */
        $puttedSector = $serializer->deserialize(json_encode($content), Sector::class, 'json');

        $existingSector = $portfolio->getSectorById($puttedSector->getId());

        if (null !== $existingSector && $puttedSector->getId() == $existingSector->getId()) {
            $existingSector->setName($puttedSector->getName());

            $entityManager->persist($existingSector);

            $this->makeLogEntry('update sector', $existingSector->getName(), $entityManager);

            $entityManager->flush();

            return new View("Sector Update Successfully", Response::HTTP_OK);
        } else {

            throw new AccessDeniedException();
        }

    }


    /**
     * @Rest\Delete("/sector/{sectorId}", name="delete_sector")
     * @param Request $request
     * @param int $sectorId
     * @param EntityManagerInterface $entityManager
     * @return View
     */
    public function deleteSector(Request $request, int $sectorId, EntityManagerInterface $entityManager): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

        $sector = $entityManager->getRepository(Sector::class)->find($sectorId);

        $affectedPositions = $entityManager->getRepository(Position::class)->findBy(['sector' => $sectorId]);
        foreach($affectedPositions as $position) {
            $position->setSector(null);
            $entityManager->persist($position);
            $this->addPositionLogEntry('Entferne gelöschten Sektor: ' . $sector->getName(), $position, $entityManager);
        }
        $entityManager->remove($sector);

        $this->makeLogEntry('delete sector', $sector, $entityManager);

        $entityManager->flush();

        return new View("Sector Delete Successfully", Response::HTTP_OK);
    }

}
