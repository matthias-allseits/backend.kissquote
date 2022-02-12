<?php

namespace App\Entity;

use App\Model\Balance;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
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
     * @var BankAccount
     * @Serializer\Type("App\Entity\BankAccount")
     * @Serializer\SerializedName("bankAccount")
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\BankAccount")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="bank_account_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $bankAccount;

    /**
     * @var Share
     * @Serializer\Type("App\Entity\Share")
     *
     * @ORM\ManyToOne(targetEntity="Share", cascade={"remove"})
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
     * @var DateTime
     *
     * @ORM\Column(name="active_until", type="date", nullable=true)
     */
    private $activeUntil;

    /**
     * @var Collection
     * @Serializer\Type("ArrayCollection<App\Entity\Transaction>")
     *
     * @ORM\OneToMany(targetEntity="Transaction", mappedBy="position", cascade={"remove"})
     */
    private $transactions;

    /**
     * @var boolean
     * @Serializer\Type("boolean")
     * @Serializer\SerializedName("isCash")
     *
     * @ORM\Column(name="isCash", type="boolean", nullable=false)
     */
    private $isCash = false;

    /**
     * @var integer|null
     * @Serializer\Type("integer")
     * @Serializer\SerializedName("shareheadId")
     *
     * @ORM\Column(name="sharehead_id", type="integer", nullable=true)
     */
    private $shareheadId;


// todo: add annotations
//    private $rates;

    /**
     * @var Balance|null
     */
    private $balance;


    public function __construct()
    {
    }


	public function __toString()
    {
        if (null !== $this->getShare()) {

            return $this->getShare()->getName() . ' (' . $this->getId() . ')';
        } else {

            return (string) $this->getId();
        }
    }


    public function getCountOfSharesByDate(\DateTime $date = null): int
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

    public function getLastDividendTransaction(): ?Transaction
    {
        $hit = null;
        if (null !== $this->transactions) {
            $transactions = array_reverse($this->getTransactions());
            foreach($transactions as $transaction) {
                if (in_array($transaction->getTitle(), self::TITLES_DIVIDEND)) {
                    $hit = $transaction;
                    break;
                }
            }
        }

        return $hit;
    }

    public function calculateNextDividendPayment(): int
    {
        $value = 0;
        $lastDividendTransaction = $this->getLastDividendTransaction();
        if (null !== $lastDividendTransaction) {
            $amountAtLastPayment = $this->getCountOfSharesByDate($lastDividendTransaction->getDate());
            if ($amountAtLastPayment > 0) {
                $dividendByShare = $lastDividendTransaction->getRate() / $amountAtLastPayment;
                $value = round($this->getCountOfSharesByDate() * $dividendByShare);
            }
        }

        return $value;
    }


    public function getCashValue(): float
    {
        $positiveTitles = ['Einzahlung', 'Vergütung', 'Verkauf', 'Forex-Gutschrift', 'Fx-Gutschrift Comp.', 'Dividende', 'Kapitalrückzahlung', 'Capital Gain'];
        $negativeTitles = ['Auszahlung', 'Kauf', 'Depotgebühren', 'Forex-Belastung', 'Fx-Belastung Comp.', 'Zins']; // todo: negativzinsen will not last forever!

        $value = 0;
        if (null !== $this->transactions) {
            foreach($this->getTransactions() as $transaction) {
                if (in_array($transaction->getTitle(), $positiveTitles)) {
//                    echo $transaction->getRate() . "\n";
                    $value += $transaction->getRate();
//                    echo 'result: ' . $value . "\n";
                } elseif (in_array($transaction->getTitle(), $negativeTitles)) {
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
     * @param Currency $currency
     */
    public function setCurrency(Currency $currency): void
    {
        $this->currency = $currency;
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
        $transactions = $this->transactions->toArray();
        $sortArray = [];
        /** @var Transaction[] $transactions */
        foreach($transactions as $transaction) {
            $sortArray[] = $transaction->getDate();
        }
        array_multisort($sortArray, SORT_ASC, $transactions);

        return $transactions;
    }

    /**
     * @param array $transactions
     */
    public function setTransactions(array $transactions): void
    {
        $this->transactions = $transactions;
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
