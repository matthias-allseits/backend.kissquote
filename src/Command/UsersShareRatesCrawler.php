<?php

namespace App\Command;

use App\Entity\Marketplace;
use App\Entity\Share;
use App\Entity\UsersShareStockrate;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;


class UsersShareRatesCrawler extends Command
{
    protected static $defaultName = 'kissquote:users-shares-rates-crawler';
    private $entityManager;
    private $output;
    private $sleep = 5;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Crawls for last rates for every user-share')
            ->setHelp('Crawls for last rates for every user-share')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Forces the flush')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('force')) {
            $force = true;
        } else {
            $force = false;
        }
        $this->output = $output;
        $date = new \DateTime();
        if ($date->format('N') == 7) {
            $date->sub(new \DateInterval('P2D'));
        } elseif ($date->format('N') == 6) {
            $date->sub(new \DateInterval('P1D'));
        }
//        $output->writeln($date->format('d.m.Y'));

        $allShares = $this->entityManager->getRepository(Share::class)->findAll();
        $filteredShares = [];
        foreach($allShares as $share) {
            $dbCheck = $this->entityManager->getRepository(UsersShareStockrate::class)->findBy(['isin' => $share->getIsin(), 'marketplace' => $share->getMarketplace(), 'currencyName' => $share->getCurrency()->getName(), 'date' => $date]);
            if (count($dbCheck) == 0) {
                $filteredShares[] = $share;
            }
        }

        $output->writeln('we are crawling for ' . count($filteredShares) . ' shares');

        /** @var Share $share */
        foreach($filteredShares as $share) {
            $output->writeln($share);

            try {
                $currency = $share->getCurrency()->getName();
                if ($currency == 'GBP') { // island apes...
                    $currency = 'GBX';
                }
                $url = 'https://www.swissquote.ch/sq_mi/public/market/Detail.action?s=' . $share->getIsin() . '_' . $share->getMarketplace()->getUrlKey() . '_' . $currency;
                $rate = $this->getRateBySwissquoteUrl($url, $share);
            } catch (\Exception $e) {
                $output->writeln('<error>rate crawling failed for url ' . $url . '</error>');
                $rate = null;
            }
            if (null === $rate) {
                $alternativeUrl = $this->getAlternativeSwissquoteUrl($share);
                if ($alternativeUrl !== $url) {
                    try {
                        $url = $alternativeUrl;
                        $rate = $this->getRateBySwissquoteUrl($url, $share);
                        $this->entityManager->persist($share);
                        // todo: make a log-entry
                    } catch(\Exception $e) {
                        $output->writeln('<error>rate crawling failed for url ' . $url . '</error>');

                        continue;
                    }
                }
            }
            $output->writeln($url);
            $output->writeln($rate);
            if (null !== $rate) {
                $this->entityManager->persist($rate);
            }
            sleep($this->sleep);
            if ($force) {
                $this->entityManager->flush();
            }
            $output->writeln('---------------------------');
        }

    }


    private function getRateBySwissquoteUrl(string $url, Share $share): ?UsersShareStockrate
    {
        $content = file_get_contents($url);
        sleep($this->sleep);
        $crawler = new Crawler($content);
        $rateCell = $crawler->filter('td.FullquoteTable__body--highlighted')->eq(0)->text();
        if (strpos($rateCell, 'Off-ex') > -1) {
            $rateCell = substr($rateCell, 0, strpos($rateCell, 'Off-ex'));
        }
        if (strpos($rateCell, 'Estim:') > -1) {
            $rateCell = substr($rateCell, 0, strpos($rateCell, 'Estim:'));
        }
        $rateCell = str_replace('&nbsp;', ' ', $rateCell);
        $rateCell = trim($rateCell);
        $rateCell = str_replace('\'', '', $rateCell);

        $currency = substr($rateCell, -3);
        $rate = (float) substr($rateCell, strpos($rateCell, ' '));
        if ($currency == 'GBX') { // island apes...
            $currency = 'GBP';
            $rate /= 100;
        }

        $dateCell = $crawler->filter('tr.FullquoteTable__body td')->eq(0)->text();
        $dateCell = trim($dateCell);
        $explDate = explode('-', $dateCell);
        $date = new \DateTime($explDate[2] . '-' . $explDate[1] . '-' . $explDate[0]);

        $stockRate = new UsersShareStockrate();
        $stockRate->setIsin($share->getIsin());
        $stockRate->setMarketplace($share->getMarketplace());
        $stockRate->setCurrencyName($currency);
        $stockRate->setRate($rate);
        $stockRate->setDate($date);

        return $stockRate;
    }


    private function getAlternativeSwissquoteUrl(Share $share): ?string {
        $currency = $share->getCurrency()->getName();
        if (
            $currency == 'USD' &&
            ($share->getMarketplace()->getName() == 'NYSE' || $share->getMarketplace()->getName() == 'SIX')
        ) {
            $altMarketplace = $this->entityManager->getRepository(Marketplace::class)->findOneBy(['name' => 'Nasdaq']);
            $share->setMarketplace($altMarketplace);
        }
        if ($currency == 'GBP') { // island apes...
            $currency = 'GBX';
        }
        $url = 'https://www.swissquote.ch/sq_mi/public/market/Detail.action?s=' . $share->getIsin() . '_' . $share->getMarketplace()->getUrlKey() . '_' . $currency;

        return $url;
    }

}
