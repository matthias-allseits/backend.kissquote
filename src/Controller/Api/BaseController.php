<?php

namespace App\Controller\Api;

use App\Entity\Currency;
use App\Entity\Label;
use App\Entity\LogEntry;
use App\Entity\Portfolio;
use App\Entity\Position;
use App\Entity\PositionLog;
use App\Entity\Sector;
use App\Entity\Strategy;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;


class BaseController extends AbstractFOSRestController
{

    /** @var Portfolio */
    protected Portfolio $portfolio;


    protected function makeLogEntry(string $action, $object, EntityManagerInterface $entityManager): void
    {
        $logEntry = new LogEntry();
        $logEntry->setPortfolio($this->portfolio);
        $logEntry->setDateTime(new \DateTime());
        $logEntry->setAction($action);
        $logEntry->setResult($object);
        $entityManager->persist($logEntry);
        $entityManager->flush();
    }


    protected function addPositionLogEntry(string $log, Position $position, EntityManagerInterface $entityManager): void
    {
        $logEntry = new PositionLog();
        $logEntry->setPosition($position);
        $logEntry->setDate(new \DateTime());
        $logEntry->setLog($log);
        $logEntry->setEmoticon('😑');
        $entityManager->persist($logEntry);
        $entityManager->flush();
    }


    /**
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Portfolio|mixed|object
     */
    protected function getPortfolioByAuth(Request $request, EntityManagerInterface $entityManager): mixed
    {
        $key = $request->headers->get('Authorization');
        $portfolio = $entityManager->getRepository(Portfolio::class)->findOneBy(['hashKey' => $key]);
        if (null === $portfolio) {
            throw new AccessDeniedException();
        } else {
            $currencies = $entityManager->getRepository(Currency::class)->findBy(['portfolioId' => $portfolio->getId()]);
            $portfolio->setCurrencies($currencies);
            $sectors = $entityManager->getRepository(Sector::class)->findBy(['portfolioId' => $portfolio->getId()]);
            $portfolio->setSectors($sectors);
            $strategies = $entityManager->getRepository(Strategy::class)->findBy(['portfolioId' => $portfolio->getId()]);
            $portfolio->setStrategies($strategies);
            $labels = $entityManager->getRepository(Label::class)->findBy(['portfolioId' => $portfolio->getId()]);
            $portfolio->setLabels($labels);
            $this->portfolio = $portfolio;
        }

        return $portfolio;
    }

}
