<?php

namespace App\Controller\Admin;

use App\Entity\BankAccount;
use App\Entity\Currency;
use App\Entity\Dividend;
use App\Entity\Feedback;
use App\Entity\FeedbackProposal;
use App\Entity\Label;
use App\Entity\LogEntry;
use App\Entity\ManualDividend;
use App\Entity\Marketplace;
use App\Entity\Portfolio;
use App\Entity\Position;
use App\Entity\PositionLog;
use App\Entity\Sector;
use App\Entity\Share;
use App\Entity\ShareheadShare;
use App\Entity\Transaction;
use App\Entity\Translation;
use App\Entity\Stockrate;
use App\Entity\Watchlist;
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
        yield MenuItem::linkToCrud('Sectors', 'fas fa-list', Sector::class);
        yield MenuItem::linkToCrud('Labels', 'fas fa-list', Label::class);
        yield MenuItem::linkToCrud('Marketplaces', 'fas fa-list', Marketplace::class);
        yield MenuItem::linkToCrud('Sharehead Shares', 'fas fa-list', ShareheadShare::class);
        yield MenuItem::linkToCrud('StockRates', 'fas fa-list', Stockrate::class);
        yield MenuItem::linkToCrud('Manual dividends', 'fas fa-list', ManualDividend::class);
        yield MenuItem::linkToCrud('Translations', 'fas fa-list', Translation::class);
        yield MenuItem::linkToCrud('Feedback Proposals', 'fas fa-list', FeedbackProposal::class);
        yield MenuItem::linkToCrud('Feedbacks', 'fas fa-list', Feedback::class);
        yield MenuItem::linkToCrud('Watchlist Entries', 'fas fa-list', Watchlist::class);
        yield MenuItem::linkToCrud('PositionLog', 'fas fa-list', PositionLog::class);
        yield MenuItem::linkToCrud('LogEntries', 'fas fa-list', LogEntry::class);
    }

}
