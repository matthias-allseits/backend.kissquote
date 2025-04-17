<?php

namespace App\Controller\Admin;

use App\Entity\BankAccount;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;


class BankAccountCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BankAccount::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('name'),
            AssociationField::new('portfolio'),
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('portfolio')
            ;
    }
}
