<?php

namespace App\Controller\Admin;

use App\Entity\BankAccount;
use App\Entity\Currency;
use App\Entity\Feedback;
use App\Entity\Label;
use App\Entity\LogEntry;
use App\Entity\ManualDividend;
use App\Entity\Marketplace;
use App\Entity\Portfolio;
use App\Entity\Position;
use App\Entity\PositionLog;
use App\Entity\Sector;
use App\Entity\Share;
use App\Entity\Transaction;
use App\Entity\Translation;
use App\Entity\Stockrate;
use App\Entity\Watchlist;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{

    public function index(): Response
    {
        return $this->render('EasyAdmin/layout.html.twig', [
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Backoffice Kissquote');
    }

    public function configureMenuItems(): iterable
    {
        MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToCrud('Portfolios', 'fas fa-list', Portfolio::class);
        yield MenuItem::linkToCrud('Shares', 'fas fa-list', Share::class);
        yield MenuItem::linkToCrud('Bank accounts', 'fas fa-list', BankAccount::class);
        yield MenuItem::linkToCrud('Positions', 'fas fa-list', Position::class);
        yield MenuItem::linkToCrud('Transactions', 'fas fa-list', Transaction::class);
        yield MenuItem::linkToCrud('Currencies', 'fas fa-list', Currency::class);
        yield MenuItem::linkToCrud('Sectors', 'fas fa-list', Sector::class);
        yield MenuItem::linkToCrud('Labels', 'fas fa-list', Label::class);
        yield MenuItem::linkToCrud('Marketplaces', 'fas fa-list', Marketplace::class);
        yield MenuItem::linkToCrud('StockRates', 'fas fa-list', Stockrate::class);
        yield MenuItem::linkToCrud('Manual dividends', 'fas fa-list', ManualDividend::class);
        yield MenuItem::linkToCrud('Translations', 'fas fa-list', Translation::class);
        yield MenuItem::linkToCrud('Feedbacks', 'fas fa-list', Feedback::class);
        yield MenuItem::linkToCrud('Watchlist Entries', 'fas fa-list', Watchlist::class);
        yield MenuItem::linkToCrud('PositionLog', 'fas fa-list', PositionLog::class);
        yield MenuItem::linkToCrud('LogEntries', 'fas fa-list', LogEntry::class);
    }

}
