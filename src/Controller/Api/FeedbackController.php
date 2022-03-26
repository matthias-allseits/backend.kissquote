<?php

namespace App\Controller\Api;

use App\Entity\Feedback;
use App\Entity\FeedbackProposal;
use App\Entity\Portfolio;
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
     * @return View
     */
    public function listProposals(Request $request, string $lang): View
    {
        $proposals = $this->getDoctrine()->getRepository(FeedbackProposal::class)->findBy(['lang' => $lang]);

        return View::create($proposals, Response::HTTP_CREATED);
    }


    /**
     * @Rest\Post("/feedback", name="create_feedback")
     * @param Request $request
     * @return View
     */
    public function createFeedback(Request $request): View
    {
        $key = $request->headers->get('Authorization');
        $portfolio = null;
        if (null !== $key) {
            $portfolio = $this->getDoctrine()->getRepository(Portfolio::class)->findOneBy(['hashKey' => $key]);
        }

        $serializer = SerializerBuilder::create()->build();
        /** @var Feedback $feedback */
        $feedback = $serializer->deserialize($request->getContent(), Feedback::class, 'json');
        $feedback->setPortfolio($portfolio);
        $feedback->setDateTime(new \DateTime());

        $this->getDoctrine()->getManager()->persist($feedback);
        $this->getDoctrine()->getManager()->flush();

        return new View("Feedback Creation Successfully", Response::HTTP_OK);
    }

}
