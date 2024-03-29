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
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;


class BaseController extends AbstractFOSRestController
{

    /** @var Portfolio */
    protected $portfolio;


    protected function makeLogEntry(string $action, $object): void
    {
        $logEntry = new LogEntry();
        $logEntry->setPortfolio($this->portfolio);
        $logEntry->setDateTime(new \DateTime());
        $logEntry->setAction($action);
        $logEntry->setResult($object);
        $this->getDoctrine()->getManager()->persist($logEntry);
        $this->getDoctrine()->getManager()->flush();
    }


    protected function addPositionLogEntry(string $log, Position $position): void
    {
        $logEntry = new PositionLog();
        $logEntry->setPosition($position);
        $logEntry->setDate(new \DateTime());
        $logEntry->setLog($log);
        $logEntry->setEmoticon('😑');
        $this->getDoctrine()->getManager()->persist($logEntry);
        $this->getDoctrine()->getManager()->flush();
    }


    /**
     * @param Request $request
     * @return Portfolio|mixed|object
     */
    protected function getPortfolioByAuth(Request $request)
    {
        $key = $request->headers->get('Authorization');
        $portfolio = $this->getDoctrine()->getRepository(Portfolio::class)->findOneBy(['hashKey' => $key]);
        if (null === $portfolio) {
            throw new AccessDeniedException();
        } else {
            $currencies = $this->getDoctrine()->getRepository(Currency::class)->findBy(['portfolioId' => $portfolio->getId()]);
            $portfolio->setCurrencies($currencies);
            $sectors = $this->getDoctrine()->getRepository(Sector::class)->findBy(['portfolioId' => $portfolio->getId()]);
            $portfolio->setSectors($sectors);
            $strategies = $this->getDoctrine()->getRepository(Strategy::class)->findBy(['portfolioId' => $portfolio->getId()]);
            $portfolio->setStrategies($strategies);
            $labels = $this->getDoctrine()->getRepository(Label::class)->findBy(['portfolioId' => $portfolio->getId()]);
            $portfolio->setLabels($labels);
            $this->portfolio = $portfolio;
        }

        return $portfolio;
    }

}
