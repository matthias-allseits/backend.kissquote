<?php

namespace App\Controller\Admin;

use App\Entity\PositionLog;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;


class PositionLogCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return PositionLog::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            AssociationField::new('position'),
            DateField::new('date'),
            TextField::new('log'),
            TextField::new('emoticon'),
            BooleanField::new('demo'),
            BooleanField::new('pinned'),
        ];
    }


    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('position')
            ->add('log')
            ->add(DateTimeFilter::new('date'))
            ;
    }

}
