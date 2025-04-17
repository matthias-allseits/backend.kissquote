<?php

namespace App\Controller\Admin;

use App\Entity\LogEntry;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;


class LogEntryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return LogEntry::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            DateTimeField::new('dateTime'),
            AssociationField::new('portfolio'),
            TextField::new('action'),
            TextField::new('result'),
            BooleanField::new('failed')
        ];
    }


    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('portfolio')
            ->add('action')
            ->add('result')
            ->add('failed')
            ;
    }

}
