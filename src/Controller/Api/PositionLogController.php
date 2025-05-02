<?php

namespace App\Controller\Api;

use App\Entity\PositionLog;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class PositionLogController extends BaseController
{

    /**
     * @Rest\Get ("/position-log/{logId}", name="get_positionlog")
     * @param Request $request
     * @param int $logId
     * @param EntityManagerInterface $entityManager
     * @return View
     */
    public function getPositionLog(Request $request, int $logId, EntityManagerInterface $entityManager): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

        $positionLog = $entityManager->getRepository(PositionLog::class)->find($logId);
        $positionLog->setPosition(null);

        return View::create($positionLog, Response::HTTP_CREATED);
    }


    /**
     * @Rest\Post("/position-log", name="create_position_log")
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return View
     */
    public function createPositionLog(Request $request, EntityManagerInterface $entityManager): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

        $serializer = SerializerBuilder::create()->build();
        $content = json_decode($request->getContent());
        $positionId = $content->positionId;
        /** @var PositionLog $positionLog */
        $positionLog = $serializer->deserialize(json_encode($content), PositionLog::class, 'json');

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

        $positionLog->setPosition(null);
        return View::create($positionLog, Response::HTTP_OK);
    }


    /**
     * @Rest\Put("/position-log/{logId}", name="update_position_log")
     * @param Request $request
     * @param int $logId
     * @param EntityManagerInterface $entityManager
     * @return View
     */
    public function updatePositionLog(Request $request, int $logId, EntityManagerInterface $entityManager): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

        /** @var PositionLog $existingPositionLog */
        $existingPositionLog = $entityManager->getRepository(PositionLog::class)->find($logId);
        if (null === $existingPositionLog) {
            throw new AccessDeniedException();
        }

        $serializer = SerializerBuilder::create()->build();
        $content = json_decode($request->getContent());
        $positionId = $content->positionId;
        /** @var PositionLog $updatedPositionLog */
        $updatedPositionLog = $serializer->deserialize(json_encode($content), PositionLog::class, 'json');

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


    /**
     * @Rest\Delete("/position-log/{logId}", name="delete_position_log")
     * @param Request $request
     * @param int $logId
     * @param EntityManagerInterface $entityManager
     * @return View
     */
    public function deletePositionLog(Request $request, int $logId, EntityManagerInterface $entityManager): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

        $positionLog = $entityManager->getRepository(PositionLog::class)->find($logId);
        $entityManager->remove($positionLog);
        $entityManager->flush();

        return new View("PositionLog Delete Successfully", Response::HTTP_OK);
    }

}
