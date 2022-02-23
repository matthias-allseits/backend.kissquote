<?php

namespace App\Controller\Api;

use App\Entity\FeedbackProposal;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
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

}
