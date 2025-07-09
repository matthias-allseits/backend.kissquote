<?php

namespace App\Controller;

use App\Entity\Currency;
use App\Entity\Feedback;
use App\Entity\LogEntry;
use App\Entity\Portfolio;
use App\Entity\Sector;
use App\Entity\Share;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;


class HomeController extends AbstractController
{

    public function home(): Response
    {
        return $this->redirectToRoute('app_portfolios');
    }


    public function portfolios(EntityManagerInterface $entityManager): Response
    {
        $portfolios = $entityManager->getRepository(Portfolio::class)->findAll();
        foreach($portfolios as $portfolio) {
            $currencies = $entityManager->getRepository(Currency::class)->findBy(['portfolioId' => $portfolio->getId()]);
            $portfolio->setCurrencies($currencies);
            $sectors = $entityManager->getRepository(Sector::class)->findBy(['portfolioId' => $portfolio->getId()]);
            $portfolio->setSectors($sectors);
//            $shares = $entityManager->getRepository(Share::class)->findBy(['portfolioId' => $portfolio->getId()]);
//            $portfolio->setShares($shares);
        }

        return $this->render('home/home.html.twig', [
            'portfolios' => $portfolios,
        ]);
    }


    public function feedbacks(EntityManagerInterface $entityManager): Response
    {
        $feedbacks = $entityManager->getRepository(Feedback::class)->findAll();

        return $this->render('home/feedback.html.twig', [
            'feedbacks' => $feedbacks,
        ]);
    }


    public function log(EntityManagerInterface $entityManager): Response
    {
        $logEntries = $entityManager->getRepository(LogEntry::class)->findBy([], ['dateTime' => 'DESC']);

        return $this->render('home/log.html.twig', [
            'logEntries' => $logEntries,
        ]);
    }


    // todo: move this method to a action-controller thing...
    public function deletePortfolio(int $portfolioId, EntityManagerInterface $entityManager): Response
    {
        $portfolio = $entityManager->getRepository(Portfolio::class)->find($portfolioId);

        foreach($portfolio->getAllPositions() as $position) {
            foreach($position->getTransactions() as $transaction) {
                $entityManager->remove($transaction);
                $entityManager->flush();
            }
            $entityManager->remove($position);
            $entityManager->flush();
        }

        $currencies = $entityManager->getRepository(Currency::class)->findBy(['portfolioId' => $portfolio->getId()]);
        foreach($currencies as $currency) {
            $entityManager->remove($currency);
        }

        $shares = $entityManager->getRepository(Share::class)->findBy(['portfolioId' => $portfolio->getId()]);
        foreach($shares as $share) {
            $entityManager->remove($share);
        }

        $entityManager->remove($portfolio);
        $entityManager->flush();

        return $this->redirectToRoute('app_home');
    }

}
