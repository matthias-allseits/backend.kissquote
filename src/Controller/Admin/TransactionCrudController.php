<?php

namespace App\Controller\Admin;

use App\Entity\Transaction;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;


class TransactionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Transaction::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            AssociationField::new('position'),
            AssociationField::new('currency'),
            TextField::new('title'),
            DateField::new('date'),
            NumberField::new('quantity'),
            NumberField::new('rate'),
            NumberField::new('fee'),
        ];
    }


    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('position')
            ->add('title')
            ->add(DateTimeFilter::new('date'))
            ;
    }

}
