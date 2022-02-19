<?php

namespace App\Command;

use App\Entity\Marketplace;
use App\Entity\Stockrate;
use App\Entity\ShareheadShare;
use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;


class ShareheadShareTransformer extends Command
{
    protected static $defaultName = 'kissquote:sharehead-transformer';
    private $entityManager;
    private $sleep = 5;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Tries to find the matching swissquote-url and marketplace of all given sharehead-shares')
            ->setHelp('Tries to find the matching swissquote-url and marketplace of all given sharehead-shares')
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

//        $marketPlaces = [
//            'CH' => 4,
//            'FR' => 25,
//            'NL' => 38,
//            'DE' => 13,
//            'GB' => 361,
//            'JE' => 361,
//            'SE' => 53,
//            'IT' => 46,
//            'DK' => 12,
//            'ES' => 1058,
//            'NO' => 48,
//            'AT' => 50,
//            'FI' => 40,
//            'BE' => 6,
//            'BM' => 65,
//            'US' => [65, 67],
//        ];

        $shares = $this->entityManager->getRepository(ShareheadShare::class)->findBy([], ['name' => 'ASC']);
        foreach($shares as $share) {
            $output->writeln($share);


            // todo: remove this after all shares have a url
            $date  = new DateTime('2022-01-24');
            $dbCheck = $this->entityManager->getRepository(Stockrate::class)->findBy(['share' => $share]);
            if (count($dbCheck) > 0) {
                $output->writeln('<info>rate already exists for this share and date</info>');
                $output->writeln('---------------------------');
                continue;
            }


            $urls = [];
            if (null === $share->getUrl()) {
                $countryKey = substr($share->getIsin(), 0, 2);
                $possibleMarketplaces = $this->entityManager->getRepository(Marketplace::class)->findBy(['isinKey' => $countryKey]);
                if (count($possibleMarketplaces) == 0 && $share->getCurrency() == 'GBP') {
                    $possibleMarketplaces = $this->entityManager->getRepository(Marketplace::class)->findBy(['isinKey' => 'GB']);
                }
                if (count($possibleMarketplaces) == 0) {
                    $output->writeln('<error>no marketplace found for isin ' . $share->getIsin() . ' and currency ' . $share->getCurrency() . '</error>');
                    $output->writeln('---------------------------');
                    sleep($this->sleep);
                    continue;
                }
                foreach($possibleMarketplaces as $marketplace) {
                    $urls[] = 'https://www.swissquote.ch/sq_mi/public/market/Detail.action?s=' . $share->getIsin() . '_' . $marketplace->getUrlKey() . '_' . $marketplace->getCurrency();
                }
            } else {
                if (null === $share->getMarketplace()) {
                    $explUrl = explode('_', $share->getUrl());
                    $marketplaceId = $explUrl[count($explUrl)-2];
                    /** @var Marketplace $marketplace */
                    $marketplace = $this->entityManager->getRepository(Marketplace::class)->findOneBy(['urlKey' => $marketplaceId]);
                    if (null !== $marketplace) {
                        $share->setMarketplace($marketplace);
                    }
                }
                $urls = [$share->getUrl()];
            }

            foreach($urls as $i => $url) {
                try {
                    $rate = $this->getRateBySwissquoteUrl($url, $share);
                    // todo: remove this for productive
                    $rate->setDate($date);
                    break;
                } catch (\Exception $e) {
                    $output->writeln('<error>rate crawling failed for url ' . $url . '</error>');
//                    $output->writeln('<error>' . $e->getMessage() . '</error>');
                    if ($i+1 == count($urls)) {
                        $output->writeln('---------------------------');
                        continue 2;
                    }
                    continue;
                }
            }
            $output->writeln($url);
            $output->writeln($rate);
            if (null === $share->getUrl()) {
                $share->setUrl($url);
            }
            $this->entityManager->persist($share);
            $dbCheck = $this->entityManager->getRepository(Stockrate::class)->findBy(['share' => $share, 'date' => $rate->getDate()]);
            if (count($dbCheck) > 0) {
                $output->writeln('<info>rate already exists for this share and date</info>');
                $output->writeln('---------------------------');
                continue;
            } else {
                $this->entityManager->persist($rate);
            }
            sleep($this->sleep);
            if ($force) {
                $this->entityManager->flush();
            }
            $output->writeln('---------------------------');
        }

    }


    private function getRateBySwissquoteUrl(string $url, ShareheadShare $share): Stockrate
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

        $stockRate = new Stockrate();
        $stockRate->setShare($share);
        $stockRate->setRate($rate);
        $stockRate->setDate($date);
        $stockRate->setCurrency($currency);

        return $stockRate;
    }

}
