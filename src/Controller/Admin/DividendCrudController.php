<?php

namespace App\Controller\Admin;

use App\Entity\Dividend;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;


class DividendCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Dividend::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            AssociationField::new('share'),
            DateField::new('date'),
            NumberField::new('valueNet'),
            NumberField::new('valueGross'),
        ];
    }

}
