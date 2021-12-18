<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;


class HomeController extends AbstractController
{

    public function home(): Response
    {
        $number = random_int(0, 100);

        return $this->render('home/home.html.twig', [
            'number' => $number,
        ]);
    }


    public function webtest(): Response
    {
        $number = random_int(0, 100);

        return $this->render('home/home.html.twig', [
            'number' => $number,
        ]);
    }

}
