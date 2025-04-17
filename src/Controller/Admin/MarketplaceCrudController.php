<?php

namespace App\Controller\Admin;

use App\Entity\Marketplace;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;


class MarketplaceCrudController extends AbstractCrudController
{

    public static function getEntityFqcn(): string
    {
        return Marketplace::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('name'),
            TextField::new('place'),
            TextField::new('urlKey'),
            TextField::new('isinKey'),
            TextField::new('currency'),
        ];
    }

}
