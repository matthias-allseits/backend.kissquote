<?php

namespace App\Controller\Admin;

use App\Entity\Portfolio;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;


class PortfolioCrudController extends AbstractCrudController
{

    public static function getEntityFqcn(): string
    {
        return Portfolio::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('username'),
            TextField::new('hashKey'),
            DateField::new('startDate'),
        ];
    }

}
