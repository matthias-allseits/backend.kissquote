<?php

namespace App\Controller;

use App\Entity\Portfolio;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;


class HomeController extends AbstractController
{

    public function home(): Response
    {
        $number = random_int(0, 100);

        return $this->redirectToRoute('app_webtest');
    }


    public function webtest(): Response
    {
        $number = random_int(0, 100);

        $portfolios = $this->getDoctrine()->getRepository(Portfolio::class)->findAll();

        return $this->render('home/home.html.twig', [
            'number' => $number,
            'portfolios' => $portfolios,
        ]);
    }

}
