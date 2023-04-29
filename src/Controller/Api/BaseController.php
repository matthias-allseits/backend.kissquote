<?php

namespace App\Controller\Api;

use App\Entity\Currency;
use App\Entity\Label;
use App\Entity\LogEntry;
use App\Entity\Portfolio;
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


    /**
     * @param Request $request
     * @return Portfolio|mixed|object
     */
    protected function getPortfolio(Request $request)
    {
        $key = $request->headers->get('Authorization');
        $portfolio = $this->getDoctrine()->getRepository(Portfolio::class)->findOneBy(['hashKey' => $key]);
        if (null === $portfolio) {
            throw new AccessDeniedException();
        } else {
            $currencies = $this->getDoctrine()->getRepository(Currency::class)->findBy(['portfolioId' => $portfolio->getId()]);
            $portfolio->setCurrencies($currencies);
            $labels = $this->getDoctrine()->getRepository(Label::class)->findBy(['portfolioId' => $portfolio->getId()]);
            $portfolio->setLabels($labels);
            $this->portfolio = $portfolio;
        }

        return $portfolio;
    }

}
