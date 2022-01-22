<?php


namespace App\Command;

use App\Entity\Stockrate;
use App\Entity\SwissquoteShare;
use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;


class SwissquoteShareCrawler extends Command
{
    protected static $defaultName = 'kissquote:swissquote-share-crawler';
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
            ->setDescription('Tries to find the swissquote-url of a given share')
            ->setHelp('Tries to find the swissquote-url of a given share')
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

        $marketPlaces = [
            'CH' => 4,
            'FR' => 25,
            'NL' => 38,
            'DE' => 13,
            'GB' => 361,
            'JE' => 361,
            'SE' => 53,
            'IT' => 46,
            'DK' => 12,
            'ES' => 1058,
            'NO' => 48,
            'AT' => 50,
            'FI' => 40,
            'BE' => 6,
            'BM' => 65,
            'US' => [65, 67],
        ];

        $shares = $this->entityManager->getRepository(SwissquoteShare::class)->findBy([], ['name' => 'ASC']);
        foreach($shares as $share) {
            $output->writeln($share);


            // todo: remove this for debugging
//            $date  = new DateTime();
//            $interval = new DateInterval('P1D');
//            $date->sub($interval);
//            $dbCheck = $this->entityManager->getRepository(Stockrate::class)->findBy(['share' => $share, 'date' => $date]);
//            if (count($dbCheck) > 0) {
//                $output->writeln('<info>rate already exists for this share and date</info>');
//                $output->writeln('---------------------------');
//                continue;
//            }


            $urls = [];
            if (null === $share->getUrl()) {
                $countryKey = substr($share->getIsin(), 0, 2);
                if (!isset($marketPlaces[$countryKey])) {
                    $output->writeln('<error>no marketplace found for isin ' . $share->getIsin() . '</error>');
                    sleep($this->sleep);
                    continue;
                }
                $marketPlaceId = $marketPlaces[$countryKey];
                $currency = $share->getCurrency();
                if ($currency == 'GBP') {
                    $currency = 'GBX';
                }
                if ($currency == 'DLR') {
                    $currency = 'USD';
                }
                if ($currency == 'SFR') {
                    $currency = 'CHF';
                }
                if (is_array($marketPlaceId)) {
                    foreach($marketPlaceId as $id) {
                        $urls[] = 'https://www.swissquote.ch/sq_mi/public/market/Detail.action?s=' . $share->getIsin() . '_' . $id . '_' . $currency;
                    }
                } else {
                    $urls[] = 'https://www.swissquote.ch/sq_mi/public/market/Detail.action?s=' . $share->getIsin() . '_' . $marketPlaceId . '_' . $currency;
                }
            } else {
                $urls = [$share->getUrl()];
            }

            foreach($urls as $i => $url) {
                try {
                    $rate = $this->getRateBySwissquoteUrl($url, $share);
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

    private function getRateBySwissquoteUrl(string $url, SwissquoteShare $share): Stockrate
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
