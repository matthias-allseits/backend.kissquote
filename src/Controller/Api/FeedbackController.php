<?php

namespace App\Controller\Api;

use App\Entity\Feedback;
use App\Entity\Portfolio;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class FeedbackController extends AbstractFOSRestController
{

    /**
     * @Rest\Get ("/feedback/proposals/{lang}", name="list_proposals")
     * @param Request $request
     * @param string $lang
     * @param EntityManagerInterface $entityManager
     * @return View
     */
    public function listProposals(Request $request, string $lang, EntityManagerInterface $entityManager): View
    {
        $proposals = $entityManager->getRepository(FeedbackProposal::class)->findBy(['lang' => $lang]);

        return View::create($proposals, Response::HTTP_CREATED);
    }


    /**
     * @Rest\Post("/feedback", name="create_feedback")
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return View
     */
    public function createFeedback(Request $request, EntityManagerInterface $entityManager): View
    {
        $key = $request->headers->get('Authorization');
        $portfolio = null;
        if (null !== $key) {
            $portfolio = $entityManager->getRepository(Portfolio::class)->findOneBy(['hashKey' => $key]);
        }

        $serializer = SerializerBuilder::create()->build();
        /** @var Feedback $feedback */
        $feedback = $serializer->deserialize($request->getContent(), Feedback::class, 'json');
        $feedback->setPortfolio($portfolio);
        $feedback->setDateTime(new \DateTime());

        $entityManager->persist($feedback);
        $entityManager->flush();

        return new View("Feedback Creation Successfully", Response::HTTP_OK);
    }

}
