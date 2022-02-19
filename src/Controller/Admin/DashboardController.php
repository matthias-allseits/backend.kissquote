<?php

namespace App\Controller\Admin;

use App\Entity\BankAccount;
use App\Entity\Currency;
use App\Entity\Dividend;
use App\Entity\Marketplace;
use App\Entity\Portfolio;
use App\Entity\Position;
use App\Entity\Share;
use App\Entity\ShareheadShare;
use App\Entity\Transaction;
use App\Entity\Translation;
use App\Entity\UsersShareStockrate;
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
            ->setTitle('Backoffice Kissquote');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToCrud('Portfolios', 'fas fa-list', Portfolio::class);
        yield MenuItem::linkToCrud('Shares', 'fas fa-list', Share::class);
        yield MenuItem::linkToCrud('Bank accounts', 'fas fa-list', BankAccount::class);
        yield MenuItem::linkToCrud('Positions', 'fas fa-list', Position::class);
        yield MenuItem::linkToCrud('Transactions', 'fas fa-list', Transaction::class);
        yield MenuItem::linkToCrud('Dividends', 'fas fa-list', Dividend::class);
        yield MenuItem::linkToCrud('Currencies', 'fas fa-list', Currency::class);
        yield MenuItem::linkToCrud('Marketplaces', 'fas fa-list', Marketplace::class);
        yield MenuItem::linkToCrud('Sharehead Shares', 'fas fa-list', ShareheadShare::class);
        yield MenuItem::linkToCrud('User-Share StockRates', 'fas fa-list', UsersShareStockrate::class);
        yield MenuItem::linkToCrud('Translations', 'fas fa-list', Translation::class);
    }

}
