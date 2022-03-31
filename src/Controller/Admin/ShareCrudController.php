<?php

namespace App\Controller\Admin;

use App\Entity\Share;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;


class ShareCrudController extends AbstractCrudController
{

    public static function getEntityFqcn(): string
    {
        return Share::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            IntegerField::new('portfolioId'),
            TextField::new('name'),
            TextField::new('shortName'),
            TextField::new('isin'),
            AssociationField::new('marketplace'),
            AssociationField::new('currency'),
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('name'))
            ->add(TextFilter::new('isin'))
            ->add(TextFilter::new('marketplace'))
            ;
    }

}
