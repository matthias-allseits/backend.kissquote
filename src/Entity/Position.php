<?php

namespace App\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

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
	 *
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="IDENTITY")
	 */
	private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="currency", type="string", length=3, nullable=false)
     */
    private $currency;

    /**
     * @var boolean
     *
     * @ORM\Column(name="active", type="boolean", nullable=false)
     */
    private $active = true;

    /**
     * @var DateTime
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
     * @var float
     *
     * @ORM\Column(name="graph_top", type="float", precision=10, scale=0, nullable=false)
     */
    private $graphTop;

    /**
     * @var float
     *
     * @ORM\Column(name="graph_bottom", type="float", precision=10, scale=0, nullable=false)
     */
    private $graphBottom;

    /**
     * @var float
     *
     * @ORM\Column(name="graph_markerdist", type="float", precision=10, scale=0, nullable=false)
     */
    private $graphMarkerdist;

    /**
     * @var float
     *
     * @ORM\Column(name="stoploss", type="float", precision=10, scale=0, nullable=true)
     */
    private $stoploss;

    /**
     * @var boolean
     *
     * @ORM\Column(name="decimals", type="integer", nullable=true)
     */
    private $decimals;

	/**
	 * @var float
	 *
	 * @ORM\Column(name="price_target", type="float", precision=10, scale=0, nullable=true)
	 */
	private $priceTarget;

	/**
	 * @var float
	 *
	 * @ORM\Column(name="price_disaster", type="float", precision=10, scale=0, nullable=true)
	 */
	private $priceDisaster;

    /**
     * @var boolean
     *
     * @ORM\Column(name="strategic", type="boolean", nullable=false)
     */
    private $strategic = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="tactical", type="boolean", nullable=false)
     */
    private $tactical = false;

    /**
     * @var Share
     *
     * @ORM\ManyToOne(targetEntity="Share")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="share_id", referencedColumnName="id")
     * })
     */
//    private $share;

    /**
     * @var Portfolio
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Portfolio")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="portfolio_id", referencedColumnName="id")
     * })
     */
    private $portfolio;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Transaction", mappedBy="position")
     */
//    private $transactions;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Stockrate", mappedBy="position")
     */
//    private $rates;


    public function __construct()
    {
        $this->transactions = new ArrayCollection();
        $this->rates = new ArrayCollection();
        $this->estimationState = 'none';
    }


	public function __toString()
    {
        if (null !== $this->getShare()) {

            return $this->getShare()->getName() . ' (' . $this->getId() . ')';
        } else {

            return (string) $this->getId();
        }
    }

    /**
     * @return DateTime
     */
    public function getStartDate(): DateTime
    {

        return $this->transactions->first()->getDate();
    }

    public function getDaysSinceStart(): int
    {
        $now = new DateTime();
        $startDate = $this->getStartDate();
        $diff = $startDate->diff($now)->format("%a");

        return $diff;
    }

    public function getProfitPerDay(): float
    {
        return round($this->getTotalReturn() / $this->getDaysSinceStart(), 1);
    }

    public function getBalance(): int
    {
        return round($this->getActualValue() - $this->getSummedInvestment());
    }

    public function getTotalReturn(): int
    {
        return round($this->getBalance() + $this->getCollectedDividends());
    }

    public function getSummedInvestment(): float
    {
	    $value = 0;
        if (null !== $this->transactions) {
            foreach($this->transactions as $transaction) {
                $value += $transaction->calculateTransactionCosts();
            }
        }

        return $value;
    }

    public function getCollectedDividends(): int
    {
	    $value = 0;
	    $today = new DateTime();
        if ($this->share->getDividends()) {
            /** @var Dividend $dividend */
            foreach($this->share->getDividends() as $dividend) {
                if (
                    $dividend->getDate() > $this->getStartDate()
                    && $dividend->getDate() < $today
                ) {
                    $gotDividend = $dividend->getValueGross();
                    $sharesAtThatDay = $this->getCountOfSharesByDate($dividend->getDate());
                    $value += $sharesAtThatDay * $gotDividend;
                }
            }
        }

        return round($value);
    }

    /**
     * @return Dividend|null
     */
    public function getNextDividend(): ?Dividend
    {
        $result = null;
        $today = new DateTime();
        if ($this->share->getDividends()) {
            /** @var Dividend $dividend */
            foreach($this->share->getDividends() as $dividend) {
                if (
                    $dividend->getDate() > $this->getStartDate()
                    && $dividend->getDate() > $today
                ) {
                    $result = $dividend;
                }
            }
        }

        return $result;
    }


    public function getCountOfSharesByDate(DateTime $date): int
    {

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


    public function getStopLossReserve(): float
    {

        return $this->getActualQuantity() * $this->getStoploss() * 0.95;
    }


    public function getActualQuantity(): int
    {
        $quantity = 0;
        if (null !== $this->transactions) {
            foreach($this->transactions as $transaction) {
                $quantity += $transaction->getQuantity();
            }
        }

        return $quantity;
    }


    public function getActualValue(): float
    {
	    $lastValue = $this->getLastRate();
        $value = $this->getActualQuantity() * $lastValue;

        return $value;
    }


    public function getCurrentDividendAmount(): int
    {
        $amount = $this->getActualQuantity() * $this->share->getCurrentDividend();

        return round($amount);
    }


    public function getHighestDividendAmount(): float
    {
        $amount = $this->getActualQuantity() * $this->share->getHighestDividendFromBalances();

        return round($amount);
    }


    public function getLastRate(): float
    {
        if ($this->rates->count() > 0) {
            return $this->rates->last()->getRate();
        } else {
            return 0;
        }
    }


    public function getAveragePrice(): float
    {
        return round($this->getSummedInvestment() / $this->getActualQuantity(), 2);
    }


    public function getFirstTransactionPrice(): float
    {
        if ($this->transactions->count() > 0) {
            return $this->transactions->first()->getRate();
        } else {
            return 0;
        }
    }


    public function getYield(): float
    {
        $yield = 0;
        if ($this->getAveragePrice() < 0) {
            $yield = 100;
        } elseif ($this->getAveragePrice() && $this->getNextDividend()) {
            $yield = 100 / $this->getAveragePrice() * $this->getNextDividend()->getValueGross();
            $yield = round($yield, 1);
        }

        return $yield;
    }


    public function getTurnoverPotential(): float
    {
        return $this->getHighestDividendAmount() - $this->getCurrentDividendAmount();
    }


    public function getOpportunityPotential(): float
    {
        return round((100 / $this->getLastRate() * $this->getPriceTarget()) - 100);
    }


    /**
     * @return integer
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param string $currency
     */
    public function setCurrency($currency): void
    {
        $this->currency = $currency;
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @param boolean $active
     */
    public function setActive($active): void
    {
        $this->active = $active;
    }

    /**
     * @return boolean
     */
    public function getActive(): bool
    {
        return $this->active;
    }

    /**
     * @param DateTime $activeFrom
     */
    public function setActiveFrom($activeFrom): void
    {
        $this->activeFrom = $activeFrom;
    }

    /**
     * @return DateTime
     */
    public function getActiveFrom(): DateTime
    {
        return $this->activeFrom;
    }

    /**
     * @param DateTime $activeUntil
     */
    public function setActiveUntil(DateTime $activeUntil): void
    {
        $this->activeUntil = $activeUntil;
    }

    /**
     * @return DateTime
     */
    public function getActiveUntil(): ?DateTime
    {
        return $this->activeUntil;
    }

    /**
     * @param float $graphTop
     * @return Position
     */
    public function setGraphTop($graphTop)
    {
        $this->graphTop = $graphTop;

        return $this;
    }

    /**
     * @return float
     */
    public function getGraphTop()
    {
        return $this->graphTop;
    }

    /**
     * @param float $graphBottom
     */
    public function setGraphBottom(float $graphBottom): void
    {
        $this->graphBottom = $graphBottom;
    }

    /**
     * @return float
     */
    public function getGraphBottom(): float
    {
        return $this->graphBottom;
    }

    /**
     * @param float $graphMarkerdist
     */
    public function setGraphMarkerdist(float $graphMarkerdist): void
    {
        $this->graphMarkerdist = $graphMarkerdist;
    }

    /**
     * @return float
     */
    public function getGraphMarkerdist(): float
    {
        return $this->graphMarkerdist;
    }

    /**
     * @param float $stoploss
     */
    public function setStoploss($stoploss): void
    {
        $this->stoploss = $stoploss;
    }

    /**
     * @return float
     */
    public function getStoploss(): float
    {
        return $this->stoploss;
    }

    /**
     * @param int $decimals
     */
    public function setDecimals(int $decimals): void
    {
        $this->decimals = $decimals;
    }

    /**
     * @return boolean
     */
    public function getDecimals(): bool
    {
        return $this->decimals;
    }

    /**
     * @param Share $share
     */
    public function setShare(Share $share): void
    {
        $this->share = $share;
    }

    /**
     * @return Share
     */
    public function getShare(): Share
    {
        return $this->share;
    }

    /**
     * @param float $priceTarget
     * @return Position
     */
    public function setPriceTarget($priceTarget): void
    {
        $this->priceTarget = $priceTarget;
    }

    /**
     * @return float
     */
    public function getPriceTarget(): float
    {
        return $this->priceTarget;
    }

    /**
     * @param Portfolio $portfolio
     */
    public function setPortfolio(Portfolio $portfolio): void
    {
        $this->portfolio = $portfolio;
    }

    /**
     * @return Portfolio
     */
    public function getPortfolio(): Portfolio
    {
        return $this->portfolio;
    }

    /**
     * @param float $priceDisaster
     */
    public function setPriceDisaster($priceDisaster): void
    {
        $this->priceDisaster = $priceDisaster;
    }

    /**
     * @return float
     */
    public function getPriceDisaster(): float
    {
        return $this->priceDisaster;
    }

    /**
     * @return Collection|Transaction[]
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function addTransaction(Transaction $transaction): void
    {
        if (!$this->transactions->contains($transaction)) {
            $this->transactions[] = $transaction;
            $transaction->setPosition($this);
        }
    }

    public function removeTransaction(Transaction $transaction): void
    {
        if ($this->transactions->contains($transaction)) {
            $this->transactions->removeElement($transaction);
            // set the owning side to null (unless already changed)
            if ($transaction->getPosition() === $this) {
                $transaction->setPosition(null);
            }
        }
    }

    /**
     * @return Collection|Stockrate[]
     */
    public function getRates(): Collection
    {
        return $this->rates;
    }

    public function addRate(Stockrate $rate): void
    {
        if (!$this->rates->contains($rate)) {
            $this->rates[] = $rate;
            $rate->setPosition($this);
        }
    }

    public function removeRate(Stockrate $rate): void
    {
        if ($this->rates->contains($rate)) {
            $this->rates->removeElement($rate);
            // set the owning side to null (unless already changed)
            if ($rate->getPosition() === $this) {
                $rate->setPosition(null);
            }
        }
    }

    /**
     * @return string
     */
    public function getEstimationState(): string
    {
        return $this->estimationState;
    }

    /**
     * @param string $estimationState
     */
    public function setEstimationState(string $estimationState): void
    {
        $this->estimationState = $estimationState;
    }

}
