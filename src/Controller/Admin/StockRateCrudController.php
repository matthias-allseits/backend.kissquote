<?php

namespace App\Controller\Admin;

use App\Entity\Stockrate;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;


class StockRateCrudController extends AbstractCrudController
{

    public static function getEntityFqcn(): string
    {
        return Stockrate::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('isin'),
            AssociationField::new('marketplace'),
            TextField::new('currencyName'),
            DateField::new('date'),
            NumberField::new('rate'),
            NumberField::new('high'),
            NumberField::new('low'),
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(DateTimeFilter::new('date'))
            ->add(TextFilter::new('currencyName'))
            ;
    }

}
