<?php

namespace App\Command;

use App\Entity\Marketplace;
use App\Entity\Position;
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
    private $verbose;

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
            ->addOption('shareId', null, InputOption::VALUE_OPTIONAL, 'Execution for only one given share')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->verbose = $input->getOption('verbose');
        $shareId = $input->getOption('shareId');
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
        $allShares = [];
        if (null !== $shareId) {
            $allShares[] = $this->entityManager->getRepository(Share::class)->find($shareId);
        } else {
            $allShares = $this->entityManager->getRepository(Share::class)->findAll();
        }

        $filteredShares = [];
        $doubleCheck = [];
        foreach($allShares as $share) {
            if (!in_array($share->getSwissquoteUrl(), $doubleCheck)) {
                $dbCheck = $this->entityManager->getRepository(Stockrate::class)->findBy(['isin' => $share->getIsin(), 'marketplace' => $share->getMarketplace(), 'currencyName' => $share->getCurrency()->getName(), 'date' => $date]);
                if (count($dbCheck) == 0 && $share->hasActivePosition()) {
                    $filteredShares[] = $share;
                    $doubleCheck[] = $share->getSwissquoteUrl();
                    continue;
                }
                $underlyingCheck = $this->entityManager->getRepository(Position::class)->findOneBy(['underlying' => $share]);
                if (null !== $underlyingCheck) {
                    $output->writeln('<info>underlying check</info>');
                    $filteredShares[] = $share;
                    $doubleCheck[] = $share->getSwissquoteUrl();
                }
            }
        }

        $output->writeln('we are crawling for ' . count($filteredShares) . ' shares');

        /** @var Share $share */
        foreach($filteredShares as $share) {
            $output->writeln($share);

            $url = '';
            try {
                $url = $share->getSwissquoteUrl();
                $rate = $this->getRateBySwissquoteApi($share);
                if ($this->verbose) {
                    $this->output->writeln('rate: ' . $rate);
                }
            } catch (\Exception $e) {
                if ($this->verbose) {
                    $this->output->writeln($e);
                }
//                $output->writeln('<error>rate crawling failed for url ' . $url . '</error>');
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
//            $output->writeln($url);
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


    private function getRateBySwissquoteApi(Share $share): ?Stockrate
    {
        $apiUrl = 'https://www.swissquote.ch/securities-retail-chart-plugin/api/chart/trading-view/intraday?resolution=1';
        $data = [
            'isin' => $share->getIsin(),
            'exchangeId' => $share->getMarketplace()->getUrlKey(),
            'currency' => $share->getCurrency()->getName(),
        ];
        $response = $this->spiderHelper->curlPostAction($apiUrl, $data);

        $stockRate = new Stockrate();
        if (isset($response->productInfo) && count($response->productInfo) > 0) {
            $lastRate = $response->productInfo[count($response->productInfo) - 1];

            $stockRate = new Stockrate();
            $stockRate->setIsin($share->getIsin());
            $stockRate->setMarketplace($share->getMarketplace());
            $stockRate->setCurrencyName($share->getCurrency()->getName());
            $stockRate->setRate($lastRate->close);
            $stockRate->setHigh($lastRate->high);
            $stockRate->setLow($lastRate->low);
            $stockRate->setDate(new \DateTime());
        }

        return $stockRate;
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
        if ($this->verbose) {
            $this->output->writeln('rateCell: ' .  $rateCell);
        }
        if (strpos($rateCell, 'Off-ex') > -1) {
            $rateCell = substr($rateCell, 0, strpos($rateCell, 'Off-ex'));
        }
        if (strpos($rateCell, 'Estim:') > -1) {
            $rateCell = substr($rateCell, 0, strpos($rateCell, 'Estim:'));
        }
        if (strpos($rateCell, 'Referenzpreis:') > -1) {
            $rateCell = substr($rateCell, strpos($rateCell, 'Referenzpreis:') + 15, 6);
            $currency = $share->getCurrency()->getName();
        }
        $rateCell = str_replace('&nbsp;', ' ', $rateCell);
        $rateCell = trim($rateCell);
        $rateCell = str_replace('\'', '', $rateCell);

        $rowNumber = 1;
        $cellNumber = 2;
        if ($share->getMarketplace()->getName() == 'Currencies') {
            $rowNumber = 0;
            $cellNumber = 0;
            $currency = substr($share->getSwissquoteUrl(), -3);
        }
        $highCell = $crawler->filter('tr.FullquoteTable__body--bidAskHighLow')->eq($rowNumber)->filter('td')->eq($cellNumber)->text();
        $highCell = str_replace('\'', '', $highCell);
        $lowCell = $crawler->filter('tr.FullquoteTable__body--bidAskHighLow')->eq($rowNumber)->filter('td')->eq($cellNumber + 1)->text();
        $lowCell = str_replace('\'', '', $lowCell);

        if ($this->verbose) {
            $this->output->writeln('rateCell: ' .  $rateCell);
        }
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

        $currency = preg_replace("/[^A-Za-z0-9 ]/", '', $currency);
        if ($this->verbose) {
            $this->output->writeln('currency: ' .  $currency);
        }
        if (strpos($share->getName(), 'BRC') > -1) { // brc handling
            if (strpos($content, 'CHF') > -1) {
                $currency = 'CHF';
            } elseif (strpos($content, 'USD') > -1) {
                $currency = 'USD';
            }
            $rate *= 10;
        }

        $dateCell = $crawler->filter('tr.FullquoteTable__body td')->eq(0)->text();
        $dateCell = trim($dateCell);
        $explDate = explode('-', $dateCell);
        if (count($explDate) > 2 && strpos($share->getName(), 'BRC') == -1) {
            $date = new \DateTime($explDate[2] . '-' . $explDate[1] . '-' . $explDate[0]);
        } else { // freestyle hack for barrier reverse convertibles
            $date = new \DateTime();
        }

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
