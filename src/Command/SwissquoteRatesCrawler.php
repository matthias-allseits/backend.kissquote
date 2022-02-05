<?php

namespace App\Command;

use App\Entity\Marketplace;
use App\Entity\Share;
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


class SwissquoteRatesCrawler extends Command
{
    protected static $defaultName = 'kissquote:swissquote-rates-crawler';
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
            ->setDescription('Crawls for last rates for every swissquote-share')
            ->setHelp('Crawls for last rates for every swissquote-share')
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

        $shares = $this->entityManager->getRepository(SwissquoteShare::class)->findBy([], ['name' => 'ASC']);
        $sortArray = [];
        foreach($shares as $share) {
            $positions = $this->entityManager->getRepository(Share::class)->findBy(['isin' => $share->getIsin()]);
            $sortArray[] = count($positions);
        }
        array_multisort($sortArray, SORT_DESC, $shares);

        foreach($shares as $share) {
            $output->writeln($share);

            if (null === $share->getUrl()) {
                $output->writeln('<error>no url available</error>');
                $output->writeln('---------------------------');
                continue;
            }
            $dbCheck = $this->entityManager->getRepository(Stockrate::class)->findBy(['share' => $share]);
            if (count($dbCheck) > 0) {
                $output->writeln('<info>rate already exists for this share and date</info>');
                $output->writeln('---------------------------');
                continue;
            }

            try {
                $rate = $this->getRateBySwissquoteUrl($share->getUrl(), $share);
            } catch (\Exception $e) {
                $output->writeln('<error>rate crawling failed for url ' . $share->getUrl() . '</error>');
                continue;
            }
            $output->writeln($share->getUrl());
            $output->writeln($rate);
            $this->entityManager->persist($rate);
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
