<?php

namespace App\Controller\Admin;

use App\Entity\BankAccount;
use App\Entity\Currency;
use App\Entity\Portfolio;
use App\Entity\Translation;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class DashboardController extends AbstractDashboardController
{

    /**
     * @Route("/admin", name="admin")
     */
    public function index(): Response
    {
        return parent::index();
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Bo Kissquote');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToCrud('Portfolios', 'fas fa-list', Portfolio::class);
        yield MenuItem::linkToCrud('Bank accounts', 'fas fa-list', BankAccount::class);
        yield MenuItem::linkToCrud('Currencies', 'fas fa-list', Currency::class);
        yield MenuItem::linkToCrud('Translations', 'fas fa-list', Translation::class);
    }
}
