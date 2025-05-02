<?php

namespace App\Controller\Api;

use App\Entity\Label;
use App\Entity\Position;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;


class LabelController extends BaseController
{

    /**
     * @Rest\Get ("/label", name="list_labels")
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return View
     */
    public function listLabels(Request $request, EntityManagerInterface $entityManager): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

        $labels = $portfolio->getLabels();

        return View::create($labels, Response::HTTP_CREATED);
    }


    /**
     * @Rest\Post("/label", name="create_label")
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return View
     */
    public function createLabel(Request $request, EntityManagerInterface $entityManager): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

        $serializer = SerializerBuilder::create()->build();
        $content = json_decode($request->getContent());
        /** @var Label $postedLabel */
        $postedLabel = $serializer->deserialize(json_encode($content), Label::class, 'json');

        $existingLabel = $portfolio->getLabelByName($postedLabel->getName());
        if (null === $existingLabel) {
            $postedLabel->setPortfolioId($portfolio->getId());

            $entityManager->persist($postedLabel);
            $entityManager->flush();
        }

        return new View("Label Creation Successfully", Response::HTTP_OK);
    }


    /**
     * @Rest\Put("/label/{labelId}", name="update_label")
     * @param Request $request
     * @param int $labelId
     * @param EntityManagerInterface $entityManager
     * @return View
     */
    public function updateLabel(Request $request, int $labelId, EntityManagerInterface $entityManager): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

        $serializer = SerializerBuilder::create()->build();
        $content = json_decode($request->getContent());
//        unset($content->balance);
//        unset($content->transactions);
//        var_dump($content);
        /** @var Label $puttedLabel */
        $puttedLabel = $serializer->deserialize(json_encode($content), Label::class, 'json');

        $existingLabel = $portfolio->getLabelById($puttedLabel->getId());

        if (null !== $existingLabel && $puttedLabel->getId() == $existingLabel->getId()) {
            $existingLabel->setName($puttedLabel->getName());
            $existingLabel->setColor($puttedLabel->getColor());

            $entityManager->persist($existingLabel);

            $this->makeLogEntry('update label', $existingLabel->getName(), $entityManager);

            $entityManager->flush();

            return new View("Label Update Successfully", Response::HTTP_OK);
        } else {

            throw new AccessDeniedException();
        }

    }


    /**
     * @Rest\Delete("/label/{labelId}", name="delete_label")
     * @param Request $request
     * @param int $labelId
     * @param EntityManagerInterface $entityManager
     * @return View
     */
    public function deleteLabel(Request $request, int $labelId, EntityManagerInterface $entityManager): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

        $label = $entityManager->getRepository(Label::class)->find($labelId);

        $query = $entityManager->createQuery(
            'SELECT p FROM App\Entity\Position p
                JOIN p.labels l
                WHERE l.id = :labelId
                ORDER BY p.id ASC'
        )
            ->setParameter('labelId', $labelId)
        ;
        /** @var Position[] $positions */
        $positions = $query->getResult();
        foreach($positions as $position) {
            $position->removeLabel($label);
            $entityManager->persist($position);
        }

        $entityManager->remove($label);

        $this->makeLogEntry('delete label', $label, $entityManager);

        $entityManager->flush();

        return new View("Label Delete Successfully", Response::HTTP_OK);
    }

}
