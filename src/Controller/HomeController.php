<?php

namespace App\Controller;

use App\Entity\Portfolio;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;


class HomeController extends AbstractController
{

    public function home(): Response
    {
        return $this->redirectToRoute('app_positions');
    }


    public function positions(): Response
    {
        $portfolios = $this->getDoctrine()->getRepository(Portfolio::class)->findAll();

        return $this->render('home/home.html.twig', [
            'portfolios' => $portfolios,
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

        $this->getDoctrine()->getManager()->remove($portfolio);
        $this->getDoctrine()->getManager()->flush();

        return $this->redirectToRoute('app_home');
    }

}
