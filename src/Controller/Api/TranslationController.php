<?php

namespace App\Controller\Api;

use App\Entity\Translation;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class TranslationController extends AbstractFOSRestController
{

    /**
     * @Rest\Get("/translations/{lang}", name="get_translations")
     * @param Request $request
     * @param string $lang
     * @param EntityManagerInterface $entityManager
     * @return View
     */
    public function translations(Request $request, string $lang, EntityManagerInterface $entityManager): View
    {
        $translations = $entityManager->getRepository(Translation::class)->findAll();
        $data = [];
        foreach($translations as $translation) {
            if ($translation->getTranslationByLang($lang)) {
                $data[] = [
                    'key' => $translation->getKey(),
                    $lang => $translation->getTranslationByLang($lang),
                ];
            }
        }

        return View::create($data, Response::HTTP_OK);
    }

}
