<?php

namespace App\Controller\Api;

use App\Entity\Position;
use App\Entity\Sector;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Serializer\SerializerInterface;


class SectorController extends BaseController
{

    #[Route('/api/sector', name: 'list_sectors', methods: ['GET', 'OPTIONS'])]
    public function listSectors(Request $request, EntityManagerInterface $entityManager): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

        $sectors = $portfolio->getSectors();

        return View::create($sectors, Response::HTTP_CREATED);
    }


    #[Route('/api/sector', name: 'create_sector', methods: ['POST', 'OPTIONS'])]
    public function createSector(Request $request, EntityManagerInterface $entityManager, SerializerInterface $serializer): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

        /** @var Sector $postedSector */
        $postedSector = $serializer->deserialize($request->getContent(), Sector::class, 'json');

        $existingSector = $portfolio->getSectorByName($postedSector->getName());
        if (null === $existingSector) {
            $postedSector->setPortfolioId($portfolio->getId());

            $entityManager->persist($postedSector);
            $entityManager->flush();
        }

        return new View($postedSector, Response::HTTP_OK);
    }


    #[Route('/api/sector/{sectorId}', name: 'update_sector', methods: ['PUT', 'OPTIONS'])]
    public function updateSector(Request $request, int $sectorId, EntityManagerInterface $entityManager, SerializerInterface $serializer): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

        /** @var Sector $puttedSector */
        $puttedSector = $serializer->deserialize($request->getContent(), Sector::class, 'json');

        $existingSector = $portfolio->getSectorById($sectorId);

        if (null !== $existingSector && $sectorId == $existingSector->getId()) {
            $existingSector->setName($puttedSector->getName());

            $entityManager->persist($existingSector);

            $this->makeLogEntry('update sector', $existingSector->getName(), $entityManager);

            $entityManager->flush();

            return new View($existingSector, Response::HTTP_OK);
        } else {

            throw new AccessDeniedException();
        }

    }


    #[Route('/api/sector/{sectorId}', name: 'delete_sector', methods: ['DELETE', 'OPTIONS'])]
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
