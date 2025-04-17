<?php

namespace App\Controller\Admin;

use App\Entity\FeedbackProposal;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;


class FeedbackProposalCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return FeedbackProposal::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            ChoiceField::new('type')->setChoices([
                'Negativ' => 'negative',
                'Neutral' => 'neutral',
                'Positiv' => 'positive',
            ]),
            ChoiceField::new('lang')->setChoices([
                'De' => 'de',
                'En' => 'en',
                'Fr' => 'fr',
            ]),
            TextareaField::new('text'),
        ];
    }
}
