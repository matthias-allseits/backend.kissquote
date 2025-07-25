<?php

namespace App\Controller\Admin;

use App\Entity\Position;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;


class PositionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Position::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            AssociationField::new('bankAccount'),
            AssociationField::new('underlying'),
            AssociationField::new('share'),
            AssociationField::new('currency'),
            AssociationField::new('sector')->hideOnIndex(),
            DateField::new('activeFrom'),
            DateField::new('activeUntil'),
            NumberField::new('shareheadId'),
            NumberField::new('manualDrawdown')->hideOnIndex(),
            NumberField::new('manualTargetPrice')->hideOnIndex(),
            DateField::new('manualDividendExDate'),
            DateField::new('manualDividendPayDate')->hideOnIndex(),
            NumberField::new('manualDividendAmount')->hideOnIndex(),
            NumberField::new('manualAveragePerformance')->hideOnIndex(),
            NumberField::new('manualLastAverageRate'),
            AssociationField::new('labels')->hideOnIndex(),
            BooleanField::new('active'),
            BooleanField::new('isCash'),
            TextareaField::new('markedLines')->hideOnIndex(),
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('bankAccount')
            ->add('underlying')
            ->add('share')
            ->add('currency')
            ->add('sector')
            ->add('active')
            ;
    }

}
