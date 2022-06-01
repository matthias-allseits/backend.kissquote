<?php

namespace App\Controller\Admin;

use App\Entity\ManualDividend;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;


class ManualDividendCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ManualDividend::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            AssociationField::new('share'),
            NumberField::new('year'),
            NumberField::new('amount'),
        ];
    }

}
