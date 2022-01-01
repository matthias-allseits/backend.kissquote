<?php

namespace App\Controller\Admin;

use App\Entity\Share;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;


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
            AssociationField::new('portfolio'),
            TextField::new('name'),
            TextField::new('shortName'),
            TextField::new('isin'),
        ];
    }

}
