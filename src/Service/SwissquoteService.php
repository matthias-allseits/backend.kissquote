<?php

namespace App\Service;

use App\Entity\ShareheadShare;
use App\Entity\Stockrate;


class SwissquoteService
{

    public function getLastQuoteByDate(ShareheadShare $share, \DateTime $date): ?Stockrate
    {
        $result = null;

        $allQuotes = $this->getQuotes($share);
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
    public function getQuotes(ShareheadShare $share): array
    {
        $rawQuotes = $this->getRawQuotes($share);

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
                    $quote = $this->createQuote($date, $splitQuote, $share);
                    $quotes[] = $quote;
                }
            }
        }

        return $quotes;
    }


    /**
     * @param \DateTime $date
     * @param $splitQuote
     * @param ShareheadShare $share
     * @return Stockrate
     */
    private function createQuote(\DateTime $date, $splitQuote, ShareheadShare $share): Stockrate
    {
        $stockrate = new Stockrate();
        $stockrate->setIsin($share->getIsin());
        $stockrate->setDate($date);
        if ($share->getCurrency() != 'GBP') {
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
     * @param ShareheadShare $share
     * @return false|string[]
     */
    private function getRawQuotes(ShareheadShare $share)
    {
        $timeToLive = 7 * 24 * 60 * 60;
        $cachePath = __DIR__ . '/../../quotesCache/';
        $fileName = $share->getId() . '.cache';

        if (file_exists($cachePath . $fileName)) {
            $lastChangeTime = filemtime($cachePath . $fileName);
            echo 'lastChangeTime: ' . $lastChangeTime . "\n";
            $gradJetzt = time();
            echo 'gradJetzt: ' . $gradJetzt . "\n";
            $timeToDie = $lastChangeTime + $timeToLive;
            echo 'timeToDie: ' . $timeToDie . "\n";
            if ($timeToDie > $gradJetzt) {
                $content = file_get_contents($cachePath . $fileName);
                $rawTicks = explode("\n", $content);

                return $rawTicks;
            }
        }

        $currencyString = $share->getCurrency();
        if ($currencyString == 'GBP') {
            $currencyString = 'GBX';
        }
        $swissquoteUrl = 'https://www.swissquote.ch/sqi_ws/HistoFromServlet?format=pipe&key=' . $share->getIsin() . '_' . $share->getMarketplace()->getUrlKey() . '_' . $currencyString . '&ftype=day&fvalue=1&ptype=a&pvalue=1';

        $rawTicks = [];
        try {
            $content = file_get_contents($swissquoteUrl);
            file_put_contents($cachePath . $fileName, $content);

            $rawTicks = explode("\n", $content);
        } catch (\Exception $e) {
            echo $e->getMessage() . "\n";
        }

        return $rawTicks;
    }

}
