<?php

namespace App\Controller\Admin;

use App\Entity\Watchlist;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;


class WatchlistCrudController extends AbstractCrudController
{

    public static function getEntityFqcn(): string
    {
        return Watchlist::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            AssociationField::new('portfolio'),
            NumberField::new('shareheadId'),
            DateField::new('startDate'),
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('portfolio')
            ;
    }

}
