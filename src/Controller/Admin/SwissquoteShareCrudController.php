<?php

namespace App\Controller\Admin;

use App\Entity\SwissquoteShare;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;


class SwissquoteShareCrudController extends AbstractCrudController
{

    public static function getEntityFqcn(): string
    {
        return SwissquoteShare::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('name'),
            TextField::new('shortname'),
            TextField::new('isin'),
            TextField::new('currency'),
            AssociationField::new('marketplace'),
            TextField::new('url'),
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('name'))
            ->add(TextFilter::new('isin'))
            ->add(TextFilter::new('url'))
            ->add(TextFilter::new('marketplace'))
            ;
    }

}
