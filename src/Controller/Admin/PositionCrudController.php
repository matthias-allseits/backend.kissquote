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
            AssociationField::new('sector'),
            DateField::new('activeFrom'),
            DateField::new('activeUntil'),
            NumberField::new('shareheadId'),
            NumberField::new('manualDrawdown')->hideOnIndex(),
            NumberField::new('manualTargetPrice')->hideOnIndex(),
            AssociationField::new('labels')->hideOnIndex(),
            BooleanField::new('active'),
            BooleanField::new('isCash'),
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
