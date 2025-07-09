<?php

namespace App\Command;

use App\Entity\Position;
use App\Entity\Share;
use App\Entity\Stockrate;
use App\Helper\SpiderHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class AvgRatesCalculator extends Command
{
    private $entityManager;
    private $spiderHelper;
    private OutputInterface $output;
    private $cache;
    private $sleep = 1;
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
            ->setName('kissquote:avg-rates')
            ->setDescription('Calculates yearly average rates for a given position')
            ->setHelp('Calculates yearly average rates for a given position')
            ->addArgument('positionId', InputArgument::REQUIRED, 'Position ID')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        $this->verbose = $input->getOption('verbose');
        $positionId = $input->getArgument('positionId');

        $position = $this->entityManager->getRepository(Position::class)->find($positionId);
        $output->writeln($position);

        $today = new \DateTime();
        $today->sub(new \DateInterval('P1Y'));
        $avgRates = [];
        for ($x = 0; $x < 10; $x++) {
            $year = $today->format('Y') - $x;
            $averageRate = $this->crawlRates($position->getShare(), $year);
            if (null !== $averageRate) {
                $avgRates[] = $averageRate;
            } else {
                if ($this->verbose) {
                    $this->output->writeln('<error>' . $year . ' - not enough lines found!</error>');
                }
            }
        }
        $avgRates = array_reverse($avgRates);
        $dividendStart = $avgRates[0];
        $dividendEnd = $avgRates[count($avgRates) - 1];
        $raisesCount = count($avgRates) - 1;
        $result = round(((pow($dividendEnd / $dividendStart, (1 / $raisesCount)) - 1) * 100), 1);
        $output->writeln('avg-prices found: ' . count($avgRates));
        $output->writeln('avg-change: ' . $result);

        return Command::SUCCESS;
    }


    private function crawlRates(Share $share, int $year): ?float
    {
        if (null === $this->cache) {
            $currencyKey = $share->getCurrency()->getName();
            if ($currencyKey == 'GBP') {
                $currencyKey = 'GBX';
            }
            $url = 'https://www.swissquote.ch/sqi_ws/HistoFromServlet?format=pipe&key=' . $share->getIsin() . '_' . $share->getMarketplace()->getUrlKey() . '_' . $currencyKey . '&ftype=day&fvalue=1&ptype=a&pvalue=1';
            $this->output->writeln($url);
            $data = $this->spiderHelper->curlAction($url);
            sleep($this->sleep);
            $this->cache = $data;
        } else {
            $data = $this->cache;
        }
        $allLines = explode("\n", $data);
        $allRates = $this->parseRates($allLines);
        $yearsRates = [];
        $sum = 0;
        foreach ($allRates as $rate) {
            if ($rate->getDate()->format('Y') == $year) {
                $yearsRates[] = $rate;
                $sum += $rate->getRate();
            }
        }
        $averageRate = null;
        if (count($yearsRates) > 99) {
            $averageRate = round($sum / count($yearsRates), 2);
        }
        // island-apes handling
        if ($share->getMarketplace()->getCurrency() == 'GBX') {
            $averageRate = round($averageRate / 100, 2);
        }
        if ($this->verbose) {
            $this->output->writeln($year . ' - lines found: ' . count($yearsRates));
            $this->output->writeln($year . ' - average rate: ' . $averageRate);
        }

        return $averageRate;
    }


    /**
     * @param array $lines
     * @return Stockrate[]
     * @throws \Exception
     */
    private function parseRates(array $lines): array
    {
        // 20220603 | 286.0 | 277.6 | 286.0 | 278.4   | 27525
        // Datum    | Hoch  | Tief  | Start | Schluss | Volumen
        $rates = [];
        foreach($lines as $i => $line) {
            $line = str_replace("'", '', $line);
            $line = str_replace("\r", '', $line);
            $cells = explode('|', $line);
            if (count($cells) > 3) {
                $rawDate = $cells[0];
                $year = substr($rawDate, 0, 4);
                $monthIndex = substr($rawDate, 4, 2) - 1;
                $day = substr($rawDate, 6, 2);
                $date = new \DateTime($year . '-' . $monthIndex . '-' . $day);
                $rate = new Stockrate();
                $rate->setDate($date);
                $rate->setRate($cells[4]);
                $rates[] = $rate;
            }
        }

        return $rates;
    }


    static function calculateAverageChange(array $balances): float
    {
        if (count($balances) > 0) {
            $dividendStart = $balances[0]->getDividend();
            $dividendEnd = $balances[count($balances) - 1]->getDividend();
            $raisesCount = count($balances) - 1;
            if ($dividendStart == 0 && isset($balances[1])) {
                $dividendStart = $balances[1]->getDividend();
                $raisesCount--;
            }
            if ($dividendStart == 0 && isset($balances[2])) {
                $dividendStart = $balances[2]->getDividend();
                $raisesCount--;
            }
            if ($dividendStart == 0 && isset($balances[3])) {
                $dividendStart = $balances[3]->getDividend();
                $raisesCount--;
            }

            if ($dividendStart > 0 && $raisesCount > 0) {
                $result = round(((pow($dividendEnd / $dividendStart, (1 / $raisesCount)) - 1) * 100), 1);

                return $result;
            }
        }

        return 0;
    }

}
