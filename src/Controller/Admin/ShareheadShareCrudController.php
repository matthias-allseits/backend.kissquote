<?php

namespace App\Controller\Admin;

use App\Entity\ShareheadShare;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;


class ShareheadShareCrudController extends AbstractCrudController
{

    public static function getEntityFqcn(): string
    {
        return ShareheadShare::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            NumberField::new('shareheadId'),
            AssociationField::new('marketplace'),
            TextField::new('name'),
            TextField::new('shortname'),
            TextField::new('isin'),
            TextField::new('currency'),
            TextField::new('url'),
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('marketplace'))
            ->add(TextFilter::new('name'))
            ->add(TextFilter::new('isin'))
            ->add(TextFilter::new('url'))
            ;
    }

}
