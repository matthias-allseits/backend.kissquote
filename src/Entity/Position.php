<?php

namespace App\Entity;

use App\Model\Balance;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;


#[ORM\Entity()]
class Position
{

    const TITLES_TRANSACTION = ['Kauf', 'Verkauf'];
    const TITLES_DIVIDEND = ['Dividende', 'Capital Gain', 'Kapitalrückzahlung'];
    const TITLES_INTEREST = ['Zins'];
    const TITLES_COUPON = ['Coupon'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[Ignore]
    #[ORM\ManyToOne(targetEntity: BankAccount::class, cascade: ["remove", "persist"], inversedBy: 'positions')]
    #[JoinColumn(name: 'bank_account_id', referencedColumnName: 'id')]
    private ?BankAccount $bankAccount;

    #[Ignore]
    #[ORM\OneToOne(targetEntity: Position::class, inversedBy: 'position')]
    #[JoinColumn(name: 'underlying_id', referencedColumnName: 'id')]
    private ?Position $underlying;

    #[Ignore]
    #[ORM\OneToOne(targetEntity: Position::class, mappedBy: 'underlying')]
    private ?Position $position;

    #[ORM\ManyToOne(targetEntity: Share::class, inversedBy: 'positions')]
    #[ORM\JoinColumn(name: 'share_id', referencedColumnName: 'id', nullable: true)]
    private ?Share $share;

    #[ORM\ManyToOne(targetEntity: Currency::class)]
    #[ORM\JoinColumn(name: 'currency_id', referencedColumnName: 'id')]
    private Currency $currency;

    #[ORM\ManyToOne(targetEntity: Sector::class)]
    #[ORM\JoinColumn(name: 'sector_id', referencedColumnName: 'id')]
    private ?Sector $sector;

    #[ORM\ManyToOne(targetEntity: Strategy::class)]
    #[ORM\JoinColumn(name: 'strategy_id', referencedColumnName: 'id')]
    private ?Strategy $strategy;

    #[ORM\Column(name: "active", type: "boolean", nullable: false)]
    private bool $active = true;

    #[Context([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d'])]
    #[ORM\Column(name: "active_from", type: "date", nullable: true)]
    private ?DateTime $activeFrom;

    #[Context([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d'])]
    #[ORM\Column(name: "active_until", type: "date", nullable: true)]
    private ?DateTime $activeUntil;

    #[ORM\OneToMany(targetEntity: Transaction::class, mappedBy: "position", cascade: ["remove", "persist"])]
    private array|Collection $transactions;

    #[ORM\OneToMany(targetEntity: PositionLog::class, mappedBy: "position", cascade: ["remove", "persist"])]
    private array|Collection $logEntries;

    #[ORM\Column(name: "is_cash", type: "boolean", nullable: false)]
    private bool $isCash = false;

    #[ORM\Column(name: "dividend_periodicity", type: "string", length: 32, unique: false, nullable: true)]
    private ?string $dividendPeriodicity;

    #[ORM\Column(name: "manual_drawdown", type: "smallint", nullable: true)]
    private ?int $manualDrawdown;

    #[ORM\Column(name: "manual_dividend_drop", type: "smallint", nullable: true)]
    private ?int $manualDividendDrop;

    #[Context([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d'])]
    #[ORM\Column(name: "manual_dividend_ex_date", type: "date", nullable: true)]
    private ?DateTime $manualDividendExDate;

    #[Context([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d'])]
    #[ORM\Column(name: "manual_dividend_pay_date", type: "date", nullable: true)]
    private ?DateTime $manualDividendPayDate;

    #[ORM\Column(name: "manual_dividend_amount", type: "float", precision: 10, scale: 3, nullable: true)]
    private ?float $manualDividendAmount;

    #[ORM\Column(name: "manual_average_performance", type: "float", precision: 10, scale: 3, nullable: true)]
    private ?float $manualAveragePerformance;

    #[ORM\Column(name: "manual_average_rate", type: "float", precision: 10, scale: 3, nullable: true)]
    private ?float $manualLastAverageRate;

    #[ORM\Column(name: "sharehead_id", type: "integer", nullable: true)]
    private ?int $shareheadId;

    #[ORM\Column(name: "stop_loss", type: "float", precision: 10, scale: 3, nullable: true)]
    private ?float $stopLoss;

    #[ORM\Column(name: "manual_dividend", type: "float", precision: 10, scale: 3, nullable: true)]
    private ?float $manualDividend;

    #[ORM\Column(name: "manual_target_price", type: "float", precision: 10, scale: 3, nullable: true)]
    private ?float $manualTargetPrice;

    #[ORM\Column(name: "target_price", type: "float", precision: 10, scale: 3, nullable: true)]
    private ?float $targetPrice;

    #[ORM\Column(name: "target_type", type: "string", length: 8, unique: false, nullable: true)]
    private ?string $targetType;

    #[ORM\Column(name: "marked_lines", type: "json", nullable: true)]
    private ?string $markedLines;

    #[ORM\ManyToMany(targetEntity: Label::class)]
    #[ORM\JoinTable(name: "position_label")]
    #[ORM\JoinColumn(name: "position_id", referencedColumnName: "id")]
    #[ORM\InverseJoinColumn(name: "label_id", referencedColumnName: "id")]
    private array|Collection $labels;

    private ?int $motherId;

    private ?Balance $balance;

    private ?string $bankAccountName;


    public function __clone()
    {
        $this->id = null;
        $newTransactions = [];
        $this->setTransactions($newTransactions);
    }


    public function __construct()
    {
        $this->labels = new ArrayCollection();
        $this->manualDrawdown = null;
        $this->manualDividend = null;
        $this->manualDividendDrop = null;
        $this->manualTargetPrice = null;
        $this->targetPrice = null;
        $this->targetType = null;
        $this->stopLoss = null;
        $this->motherId = null;
        $this->sector = null;
        $this->strategy = null;
    }

    public function __toString()
    {
        if (null !== $this->getShare()) {

            return $this->getShare()->getName() . ' (' . $this->id . ')';
        } else {

            return $this->getCurrency()->getName() . ' (' . $this->id . ')';
        }
    }



    #[Ignore]
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

    #[Ignore]
    public function getAveragePayedPriceGross(): ?float
    {
        if ($this->getCountOfSharesByDate()) {
            return round($this->getSummedInvestmentGross() / $this->getCountOfSharesByDate(), 2);
        }

        return null;
    }

    #[Ignore]
    public function getAveragePayedPriceNet(): ?float
    {
        if ($this->getCountOfSharesByDate()) {
            return round($this->getSummedInvestmentNet() / $this->getCountOfSharesByDate(), 2);
        }

        return null;
    }

    #[Ignore]
    public function getBreakEventPrice(): ?float
    {
        if ($this->getCountOfSharesByDate()) {
            return round(($this->getSummedInvestmentGross() - $this->getCollectedDividends()) / $this->getCountOfSharesByDate(), 2);
        }

        return null;
    }

    #[Ignore]
    public function getSummedInvestmentGross(): int
    {
        $value = 0;
        if (null !== $this->transactions) {
            foreach($this->getTransactions() as $transaction) {
                if ($transaction->getTitle() == 'Kauf' || $transaction->getTitle() == 'Spin-in') {
                    $value += $transaction->calculateTransactionCostsGross();
                } elseif ($transaction->getTitle() == 'Verkauf' || $transaction->getTitle() == 'Spin-off') {
                    $value -= $transaction->calculateTransactionCostsNet();
                    $value += $transaction->getFee();
                } elseif (in_array($transaction->getTitle(), ['Zins', 'Coupon'])) {
                    $value -= $transaction->getRate();
                }
            }
        }

        return round($value);
    }

    #[Ignore]
    public function getSummedInvestmentNet(): int
    {
        $value = 0;
        if (null !== $this->transactions) {
            foreach($this->getTransactions() as $transaction) {
                if ($transaction->getTitle() == 'Kauf' || $transaction->getTitle() == 'Spin-in') {
                    $value += $transaction->calculateTransactionCostsNet();
                } elseif ($transaction->getTitle() == 'Verkauf' || $transaction->getTitle() == 'Spin-off') {
                    $value -= $transaction->calculateTransactionCostsNet();
                } elseif (in_array($transaction->getTitle(), ['Zins', 'Coupon'])) {
                    $value -= $transaction->getRate();
                }
            }
        }

        return round($value);
    }

    #[Ignore]
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

    #[Ignore]
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

    #[Ignore]
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

    #[Ignore]
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

    #[Ignore]
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


    #[Ignore]
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

    #[Ignore]
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

    #[Ignore]
    public function getCashValue(): float
    {
        $value = 0;
        if (null !== $this->transactions) {
            foreach($this->getTransactions() as $transaction) {
                if ($transaction->isPositive()) {
                    $value += $transaction->getRate();
                } elseif ($transaction->isNegative()) {
                    $value -= $transaction->getRate();
                }
            }
        }

        return $value;
    }

    #[Ignore]
    public function toggleMarkable(string $key): void
    {
        $markables = json_decode($this->markedLines);
        if (is_array($markables) && in_array($key, $markables)) {
            $index = array_search($key, $markables);
            unset($markables[$index]);
        } else {
            if (!is_array($markables)) {
                $markables = [];
            }
            $markables[] = $key;
        }

        $this->markedLines = json_encode($markables);
    }


    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getBankAccount(): ?BankAccount
    {
        return $this->bankAccount;
    }

    public function setBankAccount(?BankAccount $bankAccount): void
    {
        $this->bankAccount = $bankAccount;
    }

    public function getUnderlying(): ?Position
    {
        return $this->underlying;
    }

    public function setUnderlying(?Position $underlying): void
    {
        $this->underlying = $underlying;
    }

    public function getMotherId(): ?int
    {
        return $this->motherId;
    }

    public function setMotherId(?int $motherId): void
    {
        $this->motherId = $motherId;
    }

    public function getShare(): ?Share
    {
        return $this->share;
    }

    public function setShare(?Share $share): void
    {
        $this->share = $share;
    }

    public function getCurrency(): ?Currency
    {
        return $this->currency;
    }

    public function setCurrency(?Currency $currency): void
    {
        $this->currency = $currency;
    }

    public function getSector(): ?Sector
    {
        return $this->sector;
    }

    public function setSector(?Sector $sector): void
    {
        $this->sector = $sector;
    }

    public function getStrategy(): ?Strategy
    {
        return $this->strategy;
    }

    public function setStrategy(?Strategy $strategy): void
    {
        $this->strategy = $strategy;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function getActiveFrom(): ?DateTime
    {
        return $this->activeFrom;
    }

    public function setActiveFrom(?DateTime $activeFrom): void
    {
        $this->activeFrom = $activeFrom;
    }

    public function getActiveUntil(): ?DateTime
    {
        return $this->activeUntil;
    }

    public function setActiveUntil(?DateTime $activeUntil): void
    {
        $this->activeUntil = $activeUntil;
    }

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

    public function removeTransaction(Transaction $transaction): void
    {
        $this->transactions->removeElement($transaction);
    }

    public function setTransactions(ArrayCollection|array $transactions): void
    {
        $this->transactions = $transactions;
    }

    public function getLogEntries(): Collection
    {
        return $this->logEntries;
    }

    public function setLogEntries($logEntries): void
    {
        $this->logEntries = $logEntries;
    }

    public function isCash(): bool
    {
        return $this->isCash;
    }

    public function setIsCash(bool $isCash): void
    {
        $this->isCash = $isCash;
    }

    public function getDividendPeriodicity(): ?string
    {
        return $this->dividendPeriodicity;
    }

    public function setDividendPeriodicity(?string $dividendPeriodicity): void
    {
        $this->dividendPeriodicity = $dividendPeriodicity;
    }

    public function getManualDrawdown(): ?int
    {
        return $this->manualDrawdown;
    }

    public function setManualDrawdown(?int $manualDrawdown): void
    {
        $this->manualDrawdown = $manualDrawdown;
    }

    public function getManualDividendDrop(): ?int
    {
        return $this->manualDividendDrop;
    }

    public function setManualDividendDrop(?int $manualDividendDrop): void
    {
        $this->manualDividendDrop = $manualDividendDrop;
    }

    public function getManualDividendExDate(): ?DateTime
    {
        return $this->manualDividendExDate;
    }

    public function setManualDividendExDate(?DateTime $manualDividendExDate): void
    {
        $this->manualDividendExDate = $manualDividendExDate;
    }

    public function getManualDividendPayDate(): ?DateTime
    {
        return $this->manualDividendPayDate;
    }

    public function setManualDividendPayDate(?DateTime $manualDividendPayDate): void
    {
        $this->manualDividendPayDate = $manualDividendPayDate;
    }

    public function getManualDividendAmount(): ?float
    {
        return $this->manualDividendAmount;
    }

    public function setManualDividendAmount(?float $manualDividendAmount): void
    {
        $this->manualDividendAmount = $manualDividendAmount;
    }

    public function getManualAveragePerformance(): ?float
    {
        return $this->manualAveragePerformance;
    }

    public function setManualAveragePerformance(?float $manualAveragePerformance): void
    {
        $this->manualAveragePerformance = $manualAveragePerformance;
    }

    public function getManualLastAverageRate(): ?float
    {
        return $this->manualLastAverageRate;
    }

    public function setManualLastAverageRate(?float $manualLastAverageRate): void
    {
        $this->manualLastAverageRate = $manualLastAverageRate;
    }

    public function getShareheadId(): ?int
    {
        return $this->shareheadId;
    }

    public function setShareheadId(?int $shareheadId): void
    {
        $this->shareheadId = $shareheadId;
    }

    public function getStopLoss(): ?float
    {
        return $this->stopLoss;
    }

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

    public function getManualTargetPrice(): ?float
    {
        return $this->manualTargetPrice;
    }

    public function setManualTargetPrice(?float $manualTargetPrice): void
    {
        $this->manualTargetPrice = $manualTargetPrice;
    }

    public function getTargetPrice(): ?float
    {
        return $this->targetPrice;
    }

    public function setTargetPrice(?float $targetPrice): void
    {
        $this->targetPrice = $targetPrice;
    }

    public function getTargetType(): ?string
    {
        return $this->targetType;
    }

    public function setTargetType(?string $targetType): void
    {
        $this->targetType = $targetType;
    }

    public function getMarkedLines(): ?string
    {
        return $this->markedLines;
    }

    public function setMarkedLines(?string $markedLines): void
    {
        $this->markedLines = $markedLines;
    }

    public function getLabels(): Collection
    {
        return $this->labels;
    }

    public function setLabels(Collection $labels): void
    {
        $this->labels = $labels;
    }

    public function addLabel(Label $label): void
    {
        $this->labels[] = $label;
    }

    public function removeLabel(Label $label)
    {
        $this->labels->removeElement($label);
    }

    public function getBalance(): ?Balance
    {
        return $this->balance;
    }

    public function setBalance(?Balance $balance): void
    {
        $this->balance = $balance;
    }

    public function setBankAccountName(?string $bankAccountName): void
    {
        $this->bankAccountName = $bankAccountName;
    }

    public function getBankAccountName(): ?string
    {
        return $this->bankAccountName;
    }

}
