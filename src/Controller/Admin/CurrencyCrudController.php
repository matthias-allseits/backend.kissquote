<?php

namespace App\Controller\Admin;

use App\Entity\Currency;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;


class CurrencyCrudController extends AbstractCrudController
{

    public static function getEntityFqcn(): string
    {
        return Currency::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            IntegerField::new('portfolioId'),
            TextField::new('name'),
            NumberField::new('rate'),
        ];
    }

}
