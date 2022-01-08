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
     * @ORM\ManyToOne(targetEntity="Share")
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
     * @ORM\OneToMany(targetEntity="Transaction", mappedBy="position")
     */
    private $transactions;

    /**
     * @var boolean
     *
     * @ORM\Column(name="isCash", type="boolean", nullable=false)
     */
    private $isCash = false;

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
            /** @var Transaction $transaction */
            foreach($this->transactions as $transaction) {
                if ($transaction->getDate() < $date) {
                    $quantity += $transaction->getQuantity();
                }
            }
        }

        return $quantity;
    }


    public function getAveragePayedPriceGross(): float
    {
        return round($this->getSummedInvestmentGross() / $this->getCountOfSharesByDate(), 2);
    }


    public function getAveragePayedPriceNet(): float
    {
        return round($this->getSummedInvestmentNet() / $this->getCountOfSharesByDate(), 2);
    }


    public function getSummedInvestmentGross(): int
    {
        $value = 0;
        if (null !== $this->transactions) {
            /** @var Transaction $transaction */
            foreach($this->transactions as $transaction) {
                $value += $transaction->calculateTransactionCostsGross();
            }
        }

        return round($value);
    }


    public function getSummedInvestmentNet(): int
    {
        $value = 0;
        if (null !== $this->transactions) {
            /** @var Transaction $transaction */
            foreach($this->transactions as $transaction) {
                $value += $transaction->calculateTransactionCostsNet();
            }
        }

        return round($value);
    }


    public function getSummedFees(): int
    {
        $value = 0;
        if (null !== $this->transactions) {
            /** @var Transaction $transaction */
            foreach($this->transactions as $transaction) {
                $value += $transaction->getFee();
            }
        }

        return round($value);
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
     * @param Share $share
     */
    public function setShare(Share $share): void
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
     * @return Collection
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    /**
     * @param Collection $transactions
     */
    public function setTransactions(Collection $transactions): void
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
