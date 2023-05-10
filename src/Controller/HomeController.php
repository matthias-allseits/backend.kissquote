<?php

namespace App\Controller;

use App\Entity\Currency;
use App\Entity\Feedback;
use App\Entity\LogEntry;
use App\Entity\Portfolio;
use App\Entity\Sector;
use App\Entity\Share;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;


class HomeController extends AbstractController
{

    public function home(): Response
    {
        return $this->redirectToRoute('app_portfolios');
    }


    public function portfolios(): Response
    {
        $portfolios = $this->getDoctrine()->getRepository(Portfolio::class)->findAll();
        foreach($portfolios as $portfolio) {
            $currencies = $this->getDoctrine()->getRepository(Currency::class)->findBy(['portfolioId' => $portfolio->getId()]);
            $portfolio->setCurrencies($currencies);
            $sectors = $this->getDoctrine()->getRepository(Sector::class)->findBy(['portfolioId' => $portfolio->getId()]);
            $portfolio->setSectors($sectors);
            $shares = $this->getDoctrine()->getRepository(Share::class)->findBy(['portfolioId' => $portfolio->getId()]);
            $portfolio->setShares($shares);
        }

        return $this->render('home/home.html.twig', [
            'portfolios' => $portfolios,
        ]);
    }


    public function feedbacks(): Response
    {
        $feedbacks = $this->getDoctrine()->getRepository(Feedback::class)->findAll();

        return $this->render('home/feedback.html.twig', [
            'feedbacks' => $feedbacks,
        ]);
    }


    public function log(): Response
    {
        $logEntries = $this->getDoctrine()->getRepository(LogEntry::class)->findBy([], ['dateTime' => 'DESC']);

        return $this->render('home/log.html.twig', [
            'logEntries' => $logEntries,
        ]);
    }


    // todo: move this method to a action-controller thing...
    public function deletePortfolio(int $portfolioId): Response
    {
        $portfolio = $this->getDoctrine()->getRepository(Portfolio::class)->find($portfolioId);

        foreach($portfolio->getAllPositions() as $position) {
            foreach($position->getTransactions() as $transaction) {
                $this->getDoctrine()->getManager()->remove($transaction);
                $this->getDoctrine()->getManager()->flush();
            }
            $this->getDoctrine()->getManager()->remove($position);
            $this->getDoctrine()->getManager()->flush();
        }

        $currencies = $this->getDoctrine()->getRepository(Currency::class)->findBy(['portfolioId' => $portfolio->getId()]);
        foreach($currencies as $currency) {
            $this->getDoctrine()->getManager()->remove($currency);
        }

        $shares = $this->getDoctrine()->getRepository(Share::class)->findBy(['portfolioId' => $portfolio->getId()]);
        foreach($shares as $share) {
            $this->getDoctrine()->getManager()->remove($share);
        }

        $this->getDoctrine()->getManager()->remove($portfolio);
        $this->getDoctrine()->getManager()->flush();

        return $this->redirectToRoute('app_home');
    }

}
