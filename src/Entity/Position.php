<?php

namespace App\Entity;

use App\Model\Balance;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;
use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\Exclude;

/**
 * Position
 *
 * @ORM\Table(name="position")
 * @ORM\Entity
 */
class Position
{

    const TITLES_TRANSACTION = ['Kauf', 'Verkauf'];
    const TITLES_DIVIDEND = ['Dividende', 'Capital Gain', 'Kapitalrückzahlung'];
    const TITLES_INTEREST = ['Zins'];
    const TITLES_COUPON = ['Coupon'];

	/**
	 * @var integer
     * @Serializer\Type("integer")
	 *
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="IDENTITY")
	 */
	private $id;

    /**
     * @var BankAccount|null
     * @Serializer\Type("App\Entity\BankAccount")
     * @Serializer\SerializedName("bankAccount")
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\BankAccount")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="bank_account_id", referencedColumnName="id", nullable=true)
     * })
     */
    private $bankAccount;

    /**
     * @var Position|null
     * @Serializer\Type("App\Entity\Position")
     *
     * @OneToOne(targetEntity="App\Entity\Position")
     * @JoinColumn(name="underlying_id", referencedColumnName="id", nullable=true)
     **/
    private $underlying;

    /**
     * @var Share
     * @Serializer\Type("App\Entity\Share")
     *
     * @ORM\ManyToOne(targetEntity="Share", cascade={"remove", "persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="share_id", referencedColumnName="id")
     * })
     */
    private $share;

    /**
     * @var Currency
     * @Serializer\Type("App\Entity\Currency")
     *
     * @ORM\ManyToOne(targetEntity="Currency")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="currency_id", referencedColumnName="id")
     * })
     */
    private $currency;

    /**
     * @var Sector|null
     * @Serializer\Type("App\Entity\Sector")
     *
     * @ORM\ManyToOne(targetEntity="Sector")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="sector_id", referencedColumnName="id", nullable=true)
     * })
     */
    private $sector;

    /**
     * @var Strategy|null
     * @Serializer\Type("App\Entity\Strategy")
     *
     * @ORM\ManyToOne(targetEntity="Strategy")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="strategy_id", referencedColumnName="id", nullable=true)
     * })
     */
    private $strategy;

    /**
     * @var boolean
     * @Serializer\Type("boolean")
     *
     * @ORM\Column(name="active", type="boolean", nullable=false)
     */
    private $active = true;

    /**
     * @var DateTime
     * @Serializer\Type("DateTime<'Y-m-d', '', ['Y-m-d', 'Y-m-d H:i:s']>")
     * @Serializer\SerializedName("activeFrom")
     *
     * @ORM\Column(name="active_from", type="date", nullable=true)
     */
    private $activeFrom;

    /**
     * @var DateTime|null
     * @Serializer\Type("DateTime<'Y-m-d', '', ['Y-m-d', 'Y-m-d H:i:s']>")
     * @Serializer\SerializedName("activeUntil")
     *
     * @ORM\Column(name="active_until", type="date", nullable=true)
     */
    private $activeUntil;

    /**
     * @var Collection
     * @Serializer\Type("ArrayCollection<App\Entity\Transaction>")
     *
     * @ORM\OneToMany(targetEntity="Transaction", mappedBy="position", cascade={"remove", "persist"})
     */
    private $transactions;

    /**
     * @var Collection
     * @Serializer\Type("ArrayCollection<App\Entity\PositionLog>")
     *
     * @ORM\OneToMany(targetEntity="PositionLog", mappedBy="position", cascade={"remove", "persist"})
     */
    private $logEntries;

    /**
     * @var boolean
     * @Serializer\Type("boolean")
     * @Serializer\SerializedName("isCash")
     *
     * @ORM\Column(name="isCash", type="boolean", nullable=false)
     */
    private $isCash = false;

    /**
     * @var string
     * @Serializer\Type("string")
     * @Serializer\SerializedName("dividendPeriodicity")
     *
     * @ORM\Column(name="dividend_periodicity", type="string", length=32, nullable=true)
     */
    private $dividendPeriodicity;

    /**
     * @var int|null
     * @Serializer\Type("integer")
     * @Serializer\SerializedName("manualDrawdown")
     *
     * @ORM\Column(name="manual_drawdown", type="smallint", nullable=true)
     */
    private $manualDrawdown;

    /**
     * @var int|null
     * @Serializer\Type("integer")
     * @Serializer\SerializedName("manualDividendDrop")
     *
     * @ORM\Column(name="manual_dividend_drop", type="smallint", nullable=true)
     */
    private $manualDividendDrop;

    /**
     * @var integer|null
     * @Serializer\Type("integer")
     * @Serializer\SerializedName("shareheadId")
     *
     * @ORM\Column(name="sharehead_id", type="integer", nullable=true)
     */
    private $shareheadId;

    /**
     * @var float|null
     * @Serializer\Type("float")
     * @Serializer\SerializedName("stopLoss")
     *
     * @ORM\Column(name="stop_loss", type="float", precision=10, scale=3, nullable=true)
     */
    private $stopLoss;

    /**
     * @var float|null
     * @Serializer\Type("float")
     * @Serializer\SerializedName("manualDividend")
     *
     * @ORM\Column(name="manual_dividend", type="float", precision=10, scale=3, nullable=true)
     */
    private $manualDividend;

    /**
     * @var float|null
     * @Serializer\Type("float")
     * @Serializer\SerializedName("targetPrice")
     *
     * @ORM\Column(name="target_price", type="float", precision=10, scale=3, nullable=true)
     */
    private $targetPrice;

    /**
     * @var string|null
     * @Serializer\Type("string")
     * @Serializer\SerializedName("targetType")
     *
     * @ORM\Column(name="target_type", type="string", length=8, nullable=true)
     */
    private $targetType;

    /**
     * Many Positions have Many Labels.
     * @ORM\ManyToMany(targetEntity="Label")
     * @ORM\JoinTable(name="position_label",
     *      joinColumns={@ORM\JoinColumn(name="position_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="label_id", referencedColumnName="id")}
     *      )
     * @var Collection<int, Label>
     * @Serializer\Type("ArrayCollection<App\Entity\Label>")
     */
    private $labels;

    /**
     * Used for assign this position to another position as underlying
     * @var integer|null
     * @Serializer\Type("integer")
     * @Serializer\SerializedName("motherId")
     */
    private $motherId;


// todo: add annotations
//    private $rates;

    /**
     * @var Balance|null
     */
    private $balance;


    public function __clone()
    {
        $this->id = null;
        $newTransactions = [];
//        foreach($this->getTransactions() as $transaction) {
//            $newTransaction = clone $transaction;
//            $newTransaction->setPosition($this);
//            $newTransaction->setCurrency(null);
//            $newTransactions[] = $newTransaction;
//        }
        $this->setTransactions($newTransactions);
    }


    public function __construct()
    {
        $this->labels = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function __toString()
    {
        if (null !== $this->getShare()) {

            return $this->getShare()->getName() . ' (' . $this->id . ')';
        } else {

            return $this->getCurrency()->getName() . ' (' . $this->id . ')';
        }
    }


    public function getCountOfSharesByDate(\DateTime $date = null): float
    {
        if (null == $date) {
            $date = new DateTime();
        }

        $quantity = 0;
        if (null !== $this->transactions) {
            $allTransactions = $this->getTransactions();
            $ignoreNext = false;
            foreach($allTransactions as $i => $transaction) {
                if (false === $ignoreNext) {
                    if ($transaction->getDate() < $date) {
                        if ($transaction->getTitle() == 'Kauf') {
                            $quantity += $transaction->getQuantity();
                        } elseif ($transaction->getTitle() == 'Verkauf') {
                            $quantity -= $transaction->getQuantity();
                        } elseif ($transaction->getTitle() == 'Split') {
                            $splitRatio = $allTransactions[$i + 1]->getQuantity() / $transaction->getQuantity();
                            $quantity *= $splitRatio;
                            $ignoreNext = true;
                        }
                    }
                } else {
                    $ignoreNext = false;
                }
            }
        }

        return $quantity;
    }


    public function getAveragePayedPriceGross(): ?float
    {
        if ($this->getCountOfSharesByDate()) {
            return round($this->getSummedInvestmentGross() / $this->getCountOfSharesByDate(), 2);
        }

        return null;
    }


    public function getAveragePayedPriceNet(): ?float
    {
        if ($this->getCountOfSharesByDate()) {
            return round($this->getSummedInvestmentNet() / $this->getCountOfSharesByDate(), 2);
        }

        return null;
    }


    public function getSummedInvestmentGross(): int
    {
        $value = 0;
        if (null !== $this->transactions) {
            foreach($this->getTransactions() as $transaction) {
                if ($transaction->getTitle() == 'Kauf') {
                    $value += $transaction->calculateTransactionCostsGross();
                } elseif ($transaction->getTitle() == 'Verkauf') {
                    $value -= $transaction->calculateTransactionCostsNet();
                    $value += $transaction->getFee();
                } elseif (in_array($transaction->getTitle(), ['Zins', 'Coupon'])) {
                    $value -= $transaction->getRate();
                }
            }
        }

        return round($value);
    }


    public function getSummedInvestmentNet(): int
    {
        $value = 0;
        if (null !== $this->transactions) {
            foreach($this->getTransactions() as $transaction) {
                if ($transaction->getTitle() == 'Kauf') {
                    $value += $transaction->calculateTransactionCostsNet();
                } elseif ($transaction->getTitle() == 'Verkauf') {
                    $value -= $transaction->calculateTransactionCostsNet();
                } elseif (in_array($transaction->getTitle(), ['Zins', 'Coupon'])) {
                    $value -= $transaction->getRate();
                }
            }
        }

        return round($value);
    }


    public function getSummedFees(): int
    {
        $value = 0;
        if (null !== $this->transactions) {
            foreach($this->getTransactions() as $transaction) {
                if (in_array($transaction->getTitle(), self::TITLES_TRANSACTION)) {
                    $value += $transaction->getFee();
                }
            }
        }

        return round($value);
    }

    public function getCollectedDividends(): int
    {
        $value = 0;
        if (null !== $this->transactions) {
            foreach($this->getTransactions() as $transaction) {
                if (in_array($transaction->getTitle(), self::TITLES_DIVIDEND)) {
                    $value += $transaction->getRate();
                }
            }
        }

        return round($value);
    }


    public function getCollectedDividendsCurrency(): string
    {
        $result = '';
        if (null !== $this->transactions) {
            $currencies = [];
            foreach($this->getTransactions() as $transaction) {
                if (in_array($transaction->getTitle(), self::TITLES_DIVIDEND)) {
                    $currencies[] = $transaction->getCurrency()->getName();
                }
            }
            $currencies = array_unique($currencies);
            if (count($currencies) == 1) {
                $result = $currencies[0];
            } elseif (count($currencies) > 1) {
                $result = implode('/', $currencies);
            }
        }

        return $result;
    }


    public function getCollectedInterest(): int
    {
        $value = 0;
        if (null !== $this->transactions) {
            foreach($this->getTransactions() as $transaction) {
                if (in_array($transaction->getTitle(), self::TITLES_INTEREST)) {
                    $value += $transaction->getRate();
                }
            }
        }

        return round($value);
    }


    public function getCollectedCoupons(): int
    {
        $value = 0;
        if (null !== $this->transactions) {
            foreach($this->getTransactions() as $transaction) {
                if (in_array($transaction->getTitle(), self::TITLES_COUPON)) {
                    $value += $transaction->getRate();
                }
            }
        }

        return round($value);
    }


    public function getLastDividendTransactionByDate(DateTime $startDate = null): ?Transaction
    {
        $hit = null;
        if (null !== $this->transactions) {
            $transactions = array_reverse($this->getTransactions());
            foreach($transactions as $i => $transaction) {
                if (in_array($transaction->getTitle(), self::TITLES_DIVIDEND)) {
                    if ($startDate === null || $transaction->getDate() < $startDate) {
                        $hit = $transaction;
                        // sometimes the dividends are divided in dividends and capital-gains
                        if (isset($transactions[$i + 1])) {
                            $nextTransaction = $transactions[$i + 1];
                            if (in_array($transaction->getTitle(), self::TITLES_DIVIDEND) && $transaction->getDate() == $nextTransaction->getDate()) {
                                $hit = new Transaction();
                                $hit->setTitle('Combined Dividend');
                                $hit->setDate($transaction->getDate());
                                $hit->setCurrency($transaction->getCurrency());
                                $hit->setQuantity(1);
                                $hit->setPosition($transaction->getPosition());
                                $hit->setRate($transaction->getRate() + $nextTransaction->getRate());
                                $hit->setFee($transaction->getFee() + $nextTransaction->getFee());
                            }
                        }
                        break;
                    }
                }
            }
        }

        return $hit;
    }

    public function calculateNextDividendPayment(): int
    {
        $value = 0;
        $lastDividendTransaction = $this->getLastDividendTransactionByDate();
        if (null !== $lastDividendTransaction) {
            $amountAtLastPayment = $this->getCountOfSharesByDate($lastDividendTransaction->getDate());
            if ($amountAtLastPayment > 0) {
                $dividendByShare = $lastDividendTransaction->getRate() / $amountAtLastPayment;
                if ($this->getDividendPeriodicity() == 'half-yearly') {
                    $nextTolastDividendTransaction = $this->getLastDividendTransactionByDate($lastDividendTransaction->getDate());
                    if (null !== $nextTolastDividendTransaction) {
                        $amountAtNextToLastPayment = $this->getCountOfSharesByDate($nextTolastDividendTransaction->getDate());
                        if ($amountAtNextToLastPayment > 0) {
                            $dividendByShareNextToLast = $nextTolastDividendTransaction->getRate() / $amountAtNextToLastPayment;
                        }
                    }
                }
                $value = round($this->getCountOfSharesByDate() * $dividendByShare);
                if (isset($dividendByShareNextToLast)) {
                    $value += round($this->getCountOfSharesByDate() * $dividendByShareNextToLast);
                }
            }
        }
        if ($this->getDividendPeriodicity() == 'quaterly') {
            $value *= 4;
        }

        return $value;
    }


    public function getCashValue(): float
    {
        $value = 0;
        if (null !== $this->transactions) {
            foreach($this->getTransactions() as $transaction) {
                if ($transaction->isPositive()) {
//                    echo $transaction->getRate() . "\n";
                    $value += $transaction->getRate();
//                    echo 'result: ' . $value . "\n";
                } elseif ($transaction->isNegative()) {
//                    echo $transaction->getRate() . "\n";
                    $value -= $transaction->getRate();
//                    echo 'result: ' . $value . "\n";
                }
            }
        }

//        echo 'result: ' . $value . "\n";
        return $value;
    }


    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return BankAccount|null
     */
    public function getBankAccount(): ?BankAccount
    {
        return $this->bankAccount;
    }

    /**
     * @param BankAccount|null $bankAccount
     */
    public function setBankAccount(?BankAccount $bankAccount): void
    {
        $this->bankAccount = $bankAccount;
    }

    /**
     * @return Position|null
     */
    public function getUnderlying(): ?Position
    {
        return $this->underlying;
    }

    /**
     * @param Position|null $underlying
     */
    public function setUnderlying(?Position $underlying): void
    {
        $this->underlying = $underlying;
    }

    /**
     * @return int|null
     */
    public function getMotherId(): ?int
    {
        return $this->motherId;
    }

    /**
     * @param int|null $motherId
     */
    public function setMotherId(?int $motherId): void
    {
        $this->motherId = $motherId;
    }

    /**
     * @return Share|null
     */
    public function getShare(): ?Share
    {
        return $this->share;
    }

    /**
     * @param Share|null $share
     */
    public function setShare(?Share $share): void
    {
        $this->share = $share;
    }

    /**
     * @return Currency|null
     */
    public function getCurrency(): ?Currency
    {
        return $this->currency;
    }

    /**
     * @param Currency|null $currency
     */
    public function setCurrency(?Currency $currency): void
    {
        $this->currency = $currency;
    }

    /**
     * @return Sector|null
     */
    public function getSector(): ?Sector
    {
        return $this->sector;
    }

    /**
     * @param Sector|null $sector
     */
    public function setSector(?Sector $sector): void
    {
        $this->sector = $sector;
    }

    /**
     * @return Strategy|null
     */
    public function getStrategy(): ?Strategy
    {
        return $this->strategy;
    }

    /**
     * @param Strategy|null $strategy
     */
    public function setStrategy(?Strategy $strategy): void
    {
        $this->strategy = $strategy;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * @param bool $active
     */
    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    /**
     * @return DateTime|null
     */
    public function getActiveFrom(): ?DateTime
    {
        return $this->activeFrom;
    }

    /**
     * @param DateTime|null $activeFrom
     */
    public function setActiveFrom(?DateTime $activeFrom): void
    {
        $this->activeFrom = $activeFrom;
    }

    /**
     * @return DateTime|null
     */
    public function getActiveUntil(): ?DateTime
    {
        return $this->activeUntil;
    }

    /**
     * @param DateTime|null $activeUntil
     */
    public function setActiveUntil(?DateTime $activeUntil): void
    {
        $this->activeUntil = $activeUntil;
    }

    /**
     * @return Collection|Transaction[]
     */
    public function getTransactions(): array
    {
        if (null !== $this->transactions) {
            $transactions = $this->transactions->toArray();
            $sortArray = [];
            /** @var Transaction[] $transactions */
            foreach ($transactions as $transaction) {
                $sortArray[] = $transaction->getDate();
            }
            array_multisort($sortArray, SORT_ASC, $transactions);

            return $transactions;
        }

        return [];
    }

    /**
     * @param array|null $transactions
     */
    public function setTransactions(?array $transactions): void
    {
        $this->transactions = $transactions;
    }

    /**
     * @return Collection|PositionLog[]
     */
    public function getLogEntries(): Collection
    {
        return $this->logEntries;
    }

    /**
     * @param $logEntries
     */
    public function setLogEntries($logEntries): void
    {
        $this->logEntries = $logEntries;
    }

    /**
     * @return bool
     */
    public function isCash(): bool
    {
        return $this->isCash;
    }

    /**
     * @param bool $isCash
     */
    public function setIsCash(bool $isCash): void
    {
        $this->isCash = $isCash;
    }

    /**
     * @return string|null
     */
    public function getDividendPeriodicity(): ?string
    {
        return $this->dividendPeriodicity;
    }

    /**
     * @param string|null $dividendPeriodicity
     */
    public function setDividendPeriodicity(?string $dividendPeriodicity): void
    {
        $this->dividendPeriodicity = $dividendPeriodicity;
    }

    /**
     * @return int|null
     */
    public function getManualDrawdown(): ?int
    {
        return $this->manualDrawdown;
    }

    /**
     * @param int|null $manualDrawdown
     */
    public function setManualDrawdown(?int $manualDrawdown): void
    {
        $this->manualDrawdown = $manualDrawdown;
    }

    /**
     * @return int|null
     */
    public function getManualDividendDrop(): ?int
    {
        return $this->manualDividendDrop;
    }

    /**
     * @param int|null $manualDividendDrop
     */
    public function setManualDividendDrop(?int $manualDividendDrop): void
    {
        $this->manualDividendDrop = $manualDividendDrop;
    }

    /**
     * @return int|null
     */
    public function getShareheadId(): ?int
    {
        return $this->shareheadId;
    }

    /**
     * @param int|null $shareheadId
     */
    public function setShareheadId(?int $shareheadId): void
    {
        $this->shareheadId = $shareheadId;
    }

    /**
     * @return float|null
     */
    public function getStopLoss(): ?float
    {
        return $this->stopLoss;
    }

    /**
     * @param float|null $stopLoss
     */
    public function setStopLoss(?float $stopLoss): void
    {
        $this->stopLoss = $stopLoss;
    }

    public function getManualDividend(): ?float
    {
        return $this->manualDividend;
    }

    public function setManualDividend(?float $manualDividend): void
    {
        $this->manualDividend = $manualDividend;
    }

    /**
     * @return float|null
     */
    public function getTargetPrice(): ?float
    {
        return $this->targetPrice;
    }

    /**
     * @param float|null $targetPrice
     */
    public function setTargetPrice(?float $targetPrice): void
    {
        $this->targetPrice = $targetPrice;
    }

    /**
     * @return string|null
     */
    public function getTargetType(): ?string
    {
        return $this->targetType;
    }

    /**
     * @param string|null $targetType
     */
    public function setTargetType(?string $targetType): void
    {
        $this->targetType = $targetType;
    }

    /**
     * @return Collection
     */
    public function getLabels(): Collection
    {
        return $this->labels;
    }

    /**
     * @param Collection $labels
     */
    public function setLabels(Collection $labels): void
    {
        $this->labels = $labels;
    }

    /**
     * @param Label $label
     */
    public function addLabel(Label $label): void
    {
        $this->labels[] = $label;
    }

    /**
     * @param Label $label
     */
    public function removeLabel(Label $label)
    {
        $this->labels->removeElement($label);
    }

    /**
     * @return Balance|null
     */
    public function getBalance(): ?Balance
    {
        return $this->balance;
    }

    /**
     * @param Balance|null $balance
     */
    public function setBalance(?Balance $balance): void
    {
        $this->balance = $balance;
    }

}
