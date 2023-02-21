<?php

namespace App\Command;

use App\Entity\Marketplace;
use App\Entity\Share;
use App\Entity\Stockrate;
use App\Helper\SpiderHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;


class ShareRatesCrawler extends Command
{
    protected static $defaultName = 'kissquote:rates-crawler';
    private $entityManager;
    private $spiderHelper;
    /** @var OutputInterface $output */
    private $output;
    private $sleep = 5;

    public function __construct(EntityManagerInterface $entityManager, SpiderHelper $spiderHelper)
    {
        $this->entityManager = $entityManager;
        $this->spiderHelper = $spiderHelper;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Crawls for last rates for every user-share')
            ->setHelp('Crawls for last rates for every user-share')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Forces the flush')
            ->addOption('update', null, InputOption::VALUE_NONE, 'Executes but before removes the stock-rates from this day')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('force')) {
            $force = true;
        } else {
            $force = false;
        }

        if ($input->getOption('update')) {
            $update = true;
        } else {
            $update = false;
        }

        if ($update) {
            $stockRatesToday = $this->entityManager->getRepository(Stockrate::class)->findBy(['date' => new \DateTime()]);
            foreach ($stockRatesToday as $stockrate) {
                $this->entityManager->remove($stockrate);
            }
            $this->entityManager->flush();
        }

        $this->output = $output;
        $date = new \DateTime();
        if ($date->format('N') == 7) {
            $date->sub(new \DateInterval('P2D'));
        } elseif ($date->format('N') == 6) {
            $date->sub(new \DateInterval('P1D'));
        }
//        $output->writeln($date->format('d.m.Y'));

        /** @var Share[] $allShares */
        $allShares = $this->entityManager->getRepository(Share::class)->findAll();

//        $allShares = [];
//        $allShares[] = $this->entityManager->getRepository(Share::class)->find(2251);

        $filteredShares = [];
        $doubleCheck = [];
        foreach($allShares as $share) {
            $dbCheck = $this->entityManager->getRepository(Stockrate::class)->findBy(['isin' => $share->getIsin(), 'marketplace' => $share->getMarketplace(), 'currencyName' => $share->getCurrency()->getName(), 'date' => $date]);
            if (count($dbCheck) == 0 && $share->hasActivePosition() && !in_array($share->getSwissquoteUrl(), $doubleCheck)) {
                $filteredShares[] = $share;
                $doubleCheck[] = $share->getSwissquoteUrl();
            }
        }

        $output->writeln('we are crawling for ' . count($filteredShares) . ' shares');

        /** @var Share $share */
        foreach($filteredShares as $share) {
            $output->writeln($share);

            $url = '';
            try {
                $url = $share->getSwissquoteUrl();
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


    private function getRateBySwissquoteUrl(string $url, Share $share): ?Stockrate
    {
        $content = $this->spiderHelper->curlAction($url);
        sleep($this->sleep);
        $crawler = new Crawler($content);
        $rateCell = $crawler->filter('td.FullquoteTable__body--highlighted')->eq(0)->text();
        if ($share->getMarketplace()->getName() == 'Swiss DOTS') {
            $currency = substr($rateCell, -3);
            $rateCell = $crawler->filter('tr.FullquoteTable__body--bidAskHighLow td')->eq(2)->text();
        }
        if (strpos($rateCell, 'Off-ex') > -1) {
            $rateCell = substr($rateCell, 0, strpos($rateCell, 'Off-ex'));
        }
        if (strpos($rateCell, 'Estim:') > -1) {
            $rateCell = substr($rateCell, 0, strpos($rateCell, 'Estim:'));
        }
        $rateCell = str_replace('&nbsp;', ' ', $rateCell);
        $rateCell = trim($rateCell);
        $rateCell = str_replace('\'', '', $rateCell);

        $highCell = $crawler->filter('tr.FullquoteTable__body--bidAskHighLow')->eq(1)->filter('td')->eq(2)->text();
        $lowCell = $crawler->filter('tr.FullquoteTable__body--bidAskHighLow')->eq(1)->filter('td')->eq(3)->text();

        $rate = (float) substr($rateCell, strpos($rateCell, ' '));
        $high = (float) $highCell;
        $low = (float) $lowCell;
        if (!isset($currency)) {
            $currency = substr($rateCell, -3);
        }
        if ($currency == 'GBX') { // island apes...
            $currency = 'GBP';
            $rate /= 100;
        }
        if (strpos($currency, '%') > -1) { // brc handling
            $currency = 'CHF';
        }

        $dateCell = $crawler->filter('tr.FullquoteTable__body td')->eq(0)->text();
        $dateCell = trim($dateCell);
        $explDate = explode('-', $dateCell);
        $date = new \DateTime($explDate[2] . '-' . $explDate[1] . '-' . $explDate[0]);

        $stockRate = new Stockrate();
        $stockRate->setIsin($share->getIsin());
        $stockRate->setMarketplace($share->getMarketplace());
        $stockRate->setCurrencyName($currency);
        $stockRate->setRate($rate);
        $stockRate->setHigh($high);
        $stockRate->setLow($low);
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
