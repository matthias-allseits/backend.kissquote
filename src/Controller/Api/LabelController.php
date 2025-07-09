<?php

namespace App\Controller\Api;

use App\Entity\Label;
use App\Entity\Position;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Serializer\SerializerInterface;


class LabelController extends BaseController
{

    #[Route('/api/label', name: 'list_labels', methods: ['GET', 'OPTIONS'])]
    public function listLabels(Request $request, EntityManagerInterface $entityManager): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

        $labels = $portfolio->getLabels();

        return View::create($labels, Response::HTTP_CREATED);
    }


    #[Route('/api/label', name: 'create_label', methods: ['POST', 'OPTIONS'])]
    public function createLabel(Request $request, EntityManagerInterface $entityManager, SerializerInterface $serializer): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

        /** @var Label $postedLabel */
        $postedLabel = $serializer->deserialize($request->getContent(), Label::class, 'json');

        $existingLabel = $portfolio->getLabelByName($postedLabel->getName());
        if (null === $existingLabel) {
            $postedLabel->setPortfolioId($portfolio->getId());

            $entityManager->persist($postedLabel);
            $entityManager->flush();
        }

        return new View("Label Creation Successfully", Response::HTTP_OK);
    }


    #[Route('/api/label/{labelId}', name: 'update_label', methods: ['PUT', 'OPTIONS'])]
    public function updateLabel(Request $request, int $labelId, EntityManagerInterface $entityManager, SerializerInterface $serializer): View
    {
        $portfolio = $this->getPortfolioByAuth($request, $entityManager);

        /** @var Label $puttedLabel */
        $puttedLabel = $serializer->deserialize($request->getContent(), Label::class, 'json');

        $existingLabel = $portfolio->getLabelById($labelId);

        if (null !== $existingLabel && $existingLabel->getId() == $labelId) {
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


    #[Route('/api/label/{labelId}', name: 'delete_label', methods: ['DELETE', 'OPTIONS'])]
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
