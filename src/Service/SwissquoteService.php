<?php

namespace App\Service;

use App\Entity\Marketplace;
use App\Entity\Share;
use App\Entity\ShareheadShare;
use App\Entity\Stockrate;


class SwissquoteService
{

    public function getLastQuoteByDate(\DateTime $date, object $share): ?Stockrate
    {
        $result = null;

        $allQuotes = [];
        if ($share instanceof ShareheadShare) {
            $allQuotes = $this->getQuotesByShareheadShare($share);
        } elseif ($share instanceof Share) {
            $allQuotes = $this->getQuotesByShare($share);
        }
        foreach($allQuotes as $quote) {
            if ($quote->getDate() <= $date) {
                $result = $quote;
            } else {
                break;
            }
        }

        return $result;
    }


    /**
     * @param ShareheadShare $share
     * @return Stockrate[]
     * @throws \Exception
     */
    public function getQuotesByShareheadShare(ShareheadShare $share): array
    {
        $rawQuotes = $this->getRawQuotes($share->getIsin(), $share->getCurrency(), $share->getMarketplace());

        $quotes = $this->parseQuotes($rawQuotes, $share->getIsin(), $share->getCurrency());

        return $quotes;
    }


    /**
     * @param Share $share
     * @return Stockrate[]
     * @throws \Exception
     */
    public function getQuotesByShare(Share $share): array
    {
        $rawQuotes = $this->getRawQuotes($share->getIsin(), $share->getCurrency(), $share->getMarketplace());

        $quotes = $this->parseQuotes($rawQuotes, $share->getIsin(), $share->getCurrency());

        return $quotes;
    }


    private function parseQuotes(array $rawQuotes, string $isin, string $currency): array
    {
        $startDate = new \DateTime();
        $startDate->sub(new \DateInterval('P20Y'));

        $quotes = [];
        foreach ($rawQuotes as $rawQuote) {
            // Datum | Hoch | Tief | Start | Schluss | Volumen
            if (strlen($rawQuote) > 3) {
                $splitQuote = explode("|", $rawQuote);
                $date = new \DateTime(substr($splitQuote[0], 0, 4) . '-' . substr($splitQuote[0], 4, 2) . '-' . substr($splitQuote[0], 6, 2));
                if ($date >= $startDate) {
//                    echo $date->format('Y-m-d') . "\n";
//                    echo $rawQuote . "\n";
                    $quote = $this->createQuote($date, $splitQuote, $isin, $currency);
                    $quotes[] = $quote;
                }
            }
        }

        return $quotes;
    }


    /**
     * @param \DateTime $date
     * @param $splitQuote
     * @param string $isin
     * @param string $currency
     * @return Stockrate
     */
    private function createQuote(\DateTime $date, $splitQuote, string $isin, string $currency): Stockrate
    {
        $stockrate = new Stockrate();
        $stockrate->setIsin($isin);
        $stockrate->setDate($date);
        if ($currency != 'GBP') {
            $stockrate->setRate($splitQuote[4]);
            $stockrate->setHigh($splitQuote[1]);
            $stockrate->setLow($splitQuote[2]);
        } else {
            $stockrate->setRate($splitQuote[4] / 100);
            $stockrate->setHigh($splitQuote[1] / 100);
            $stockrate->setLow($splitQuote[2] / 100);
        }

        return $stockrate;
    }


    /**
     * @return false|string[]
     */
    private function getRawQuotes(string $isin, string $currencyString, ?Marketplace $marketplace = null)
    {
        $rawTicks = [];
        if (null !== $marketplace) {
            $timeToLive = 7 * 24 * 60 * 60;
            $cachePath = __DIR__ . '/../../quotesCache/';

            if ($currencyString == 'GBP') {
                $currencyString = 'GBX';
            }
            $combinedStrangeString = $isin . '_' . $marketplace->getUrlKey() . '_' . $currencyString;
            $fileName = $combinedStrangeString;

            if (file_exists($cachePath . $fileName)) {
                $lastChangeTime = filemtime($cachePath . $fileName);
//            echo 'lastChangeTime: ' . $lastChangeTime . "\n";
                $gradJetzt = time();
//            echo 'gradJetzt: ' . $gradJetzt . "\n";
                $timeToDie = $lastChangeTime + $timeToLive;
//            echo 'timeToDie: ' . $timeToDie . "\n";
                if ($timeToDie > $gradJetzt) {
                    $content = file_get_contents($cachePath . $fileName);
                    $rawTicks = explode("\n", $content);

                    return $rawTicks;
                }
            }
            $swissquoteUrl = 'https://www.swissquote.ch/sqi_ws/HistoFromServlet?format=pipe&key=' . $combinedStrangeString . '&ftype=day&fvalue=1&ptype=a&pvalue=1';

            try {
                $content = file_get_contents($swissquoteUrl);
                file_put_contents($cachePath . $fileName, $content);

                $rawTicks = explode("\n", $content);
            } catch (\Exception $e) {
                file_put_contents($cachePath . $fileName, '');
//                echo $e->getMessage() . "\n";
            }
        }

        return $rawTicks;
    }

}
