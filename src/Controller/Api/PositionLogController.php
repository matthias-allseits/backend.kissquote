<?php

namespace App\Controller\Api;

use App\Entity\PositionLog;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\View\View;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;


class PositionLogController extends BaseController
{

    #[Route('/api/position/{positionId}/position-log', name: 'create_position_log', methods: ['POST', 'OPTIONS'])]
    public function createPositionLog(Request $request, int $positionId, EntityManagerInterface $entityManager, SerializerInterface $serializer): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

        /** @var PositionLog $positionLog */
        $positionLog = $serializer->deserialize($request->getContent(), PositionLog::class, 'json');

        $position = $portfolio->getPositionById($positionId);
        if (null === $position) {
            throw new AccessDeniedException();
        } else {
            $positionLog->setPosition($position);
        }

        $entityManager->persist($position);
        $entityManager->persist($positionLog);

        $this->makeLogEntry('create new PositionLog', $positionLog, $entityManager);

        $entityManager->flush();

        return View::create($positionLog, Response::HTTP_OK);
    }


    #[Route('/api/position/{positionId}/position-log/{logId}', name: 'update_position_log', methods: ['PUT', 'OPTIONS'])]
    public function updatePositionLog(Request $request, int $positionId, int $logId, EntityManagerInterface $entityManager, SerializerInterface $serializer): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

        /** @var PositionLog $existingPositionLog */
        $existingPositionLog = $entityManager->getRepository(PositionLog::class)->find($logId);
        if (null === $existingPositionLog) {
            throw new AccessDeniedException();
        }

        /** @var PositionLog $updatedPositionLog */
        $updatedPositionLog = $serializer->deserialize($request->getContent(), PositionLog::class, 'json');

        $position = $portfolio->getPositionById($positionId);
        if (null === $position) {
            throw new AccessDeniedException();
        } else {
            $updatedPositionLog->setPosition($position);
        }

        $existingPositionLog->setDate($updatedPositionLog->getDate());
        $existingPositionLog->setLog($updatedPositionLog->getLog());
        $existingPositionLog->setEmoticon($updatedPositionLog->getEmoticon());
        if ($updatedPositionLog->isPinned()) {
            foreach($position->getLogEntries() as $entry) {
                $entry->setPinned(false);
            }
        }
        $existingPositionLog->setPinned($updatedPositionLog->isPinned());

        $entityManager->persist($position);
        $entityManager->persist($existingPositionLog);

        $this->makeLogEntry('update Position-Log', $existingPositionLog, $entityManager);

        $entityManager->flush();

        $updatedPositionLog->setPosition(null);
        return View::create($updatedPositionLog, Response::HTTP_OK);
    }


    #[Route('/api/position/{positionId}/position-log/{logId}', name: 'delete_position_log', methods: ['DELETE', 'OPTIONS'])]
    public function deletePositionLog(Request $request, int $logId, EntityManagerInterface $entityManager): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

        $positionLog = $entityManager->getRepository(PositionLog::class)->find($logId);
        $entityManager->remove($positionLog);
        $entityManager->flush();

        return new View("PositionLog Delete Successfully", Response::HTTP_OK);
    }

}
