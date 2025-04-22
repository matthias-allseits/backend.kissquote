<?php

namespace App\Entity;

use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;


#[ORM\Entity()]
class Portfolio
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(name: "user_name", type: "string", length: 32, unique: false, nullable: true)]
    private ?string $userName;

    #[ORM\Column(name: "hash_key", type: "string", length: 32, unique: false, nullable: true)]
    private ?string $hashKey;

    #[ORM\Column(name: "start_date", type: "date", nullable: false)]
    private DateTime $startDate;

    #[ORM\OneToMany(targetEntity: BankAccount::class, mappedBy: "portfolio", cascade: ["remove", "persist"])]
    private array|Collection $bankAccounts;

    #[ORM\OneToMany(targetEntity: Watchlist::class, mappedBy: "portfolio", cascade: ["remove", "persist"])]
    private array|Collection $watchlistEntries;

    /**
     * @var Share[]
     * @Serializer\Exclude()
     */
    private array $shares;

    /**
     * @var Currency[]
     * @Serializer\Exclude()
     */
    private array $currencies;

    /**
     * @var Sector[]
     * @Serializer\Exclude()
     */
    private array $sectors;

    /**
     * @var Strategy[]
     * @Serializer\Exclude()
     */
    private array $strategies;

    /**
     * @var Label[]
     * @Serializer\Exclude()
     */
    private array $labels;


    public function __clone() {
        $this->id = null;
        $newAccounts = [];
        foreach($this->getBankAccounts() as $account) {
            $newAccount = clone $account;
//            $newAccount->setPositions([]);
            $newAccount->setPortfolio($this);
            $newAccounts[] = $newAccount;
        }
        $this->setBankAccounts($newAccounts);
        foreach($this->getAllPositions() as $position) {
            if (null !== $position->getShare()) {
                $position->getShare()->setPortfolioId($this->id);
            }
        }
        foreach($this->getCurrencies() as $currency) {
            $currency->setPortfolioId($this->id);
        }
    }

    public function __construct()
    {
        $this->startDate = new DateTime();
    }

    public function __toString()
    {
        return (string) $this->userName;
    }


    /**
     * @return Position[]
     */
    public function getAllPositions(): array
    {
        $positions = [];
        /** @var BankAccount $account */
        foreach($this->bankAccounts as $account) {
            $positions = array_merge($positions, $account->getPositions());
        }
        $filteredPositions = [];
        /** @var Position $position */
        foreach($positions as $position) {
                $filteredPositions[] = $position;
        }

        return $filteredPositions;
    }


    public function getBankAccountById(int $id): ?BankAccount
    {
        $hit = null;
        foreach ($this->bankAccounts as $account) {
            if ($account->getId() == $id) {
                $hit = $account;
            }
        }

        return $hit;
    }


    public function getPositionById(int $id): ?Position
    {
        $hit = null;
        foreach ($this->bankAccounts as $account) {
            foreach($account->getPositions() as $position) {
                if ($position->getId() == $id) {
                    $hit = $position;
                }
            }
        }

        return $hit;
    }


    public function getShareByIsin(string $isin): ?Share
    {
        $hit = null;
        if (is_array($this->shares)) {
            foreach ($this->shares as $share) {
                if ($share->getIsin() == $isin) {
                    $hit = $share;
                }
            }
        }

        return $hit;
    }


    public function getCurrencyByName(string $name): ?Currency
    {
        $hit = null;
        foreach ($this->currencies as $currency) {
            if ($currency->getName() == $name) {
                $hit = $currency;
            }
        }

        return $hit;
    }


    public function getSectorByName(string $name): ?Sector
    {
        $hit = null;
        if (is_array($this->sectors)) {
            foreach ($this->sectors as $sector) {
                if ($sector->getName() == $name) {
                    $hit = $sector;
                }
            }
        }

        return $hit;
    }


    public function getStrategyByName(string $name): ?Strategy
    {
        $hit = null;
        if (is_array($this->strategies)) {
            foreach ($this->strategies as $strategy) {
                if ($strategy->getName() == $name) {
                    $hit = $strategy;
                }
            }
        }

        return $hit;
    }


    public function getCurrencyById(int $id): ?Currency
    {
        $hit = null;
        foreach ($this->currencies as $currency) {
            if ($currency->getId() == $id) {
                $hit = $currency;
            }
        }

        return $hit;
    }


    public function getManualDividendById(int $id): ?ManualDividend
    {
        $hit = null;
        foreach ($this->shares as $share) {
            foreach($share->getManualDividends() as $manualDividend) {
                if ($manualDividend->getId() == $id) {
                    $hit = $manualDividend;
                }
            }
        }

        return $hit;
    }


    public function getSectorById(int $id): ?Sector
    {
        $hit = null;
        foreach ($this->sectors as $sector) {
            if ($sector->getId() == $id) {
                $hit = $sector;
            }
        }

        return $hit;
    }


    public function getStrategyById(int $id): ?Strategy
    {
        $hit = null;
        foreach ($this->strategies as $strategy) {
            if ($strategy->getId() == $id) {
                $hit = $strategy;
            }
        }

        return $hit;
    }


    public function getLabelByName(string $name): ?Label
    {
        $hit = null;
        if ($this->labels) {
            foreach ($this->labels as $label) {
                if ($label->getName() == $name) {
                    $hit = $label;
                }
            }
        }

        return $hit;
    }


    public function getLabelById(int $id): ?Label
    {
        $hit = null;
        foreach ($this->labels as $label) {
            if ($label->getId() == $id) {
                $hit = $label;
            }
        }

        return $hit;
    }


    /**
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getUserName(): ?string
    {
        return $this->userName;
    }

    /**
     * @param string|null $userName
     */
    public function setUserName(?string $userName): void
    {
        $this->userName = $userName;
    }

    /**
     * @return string|null
     */
    public function getHashKey(): ?string
    {
        return $this->hashKey;
    }

    /**
     * @param string|null $hashKey
     */
    public function setHashKey(?string $hashKey): void
    {
        $this->hashKey = $hashKey;
    }

    /**
     * @return DateTime
     */
    public function getStartDate(): DateTime
    {
        return $this->startDate;
    }

    /**
     * @param DateTime $startDate
     */
    public function setStartDate(DateTime $startDate): void
    {
        $this->startDate = $startDate;
    }

    /**
     * @param BankAccount $bankAccount
     */
    public function addBankAccount(BankAccount $bankAccount): void
    {
        $this->bankAccounts[] = $bankAccount;
    }

    /**
     * @param BankAccount $bankAccount
     */
    public function removeBankAccount(BankAccount $bankAccount)
    {
        $this->bankAccounts->removeElement($bankAccount);
    }

    /**
     * @param BankAccount[]|Collection $bankAccounts
     */
    public function setBankAccounts($bankAccounts): void
    {
        $this->bankAccounts = $bankAccounts;
    }

    /**
     * @return Collection|BankAccount[]
     */
    public function getBankAccounts(): Collection
    {
        return $this->bankAccounts;
    }

    /**
     * @return Watchlist[]|Collection
     */
    public function getWatchlistEntries()
    {
        return $this->watchlistEntries;
    }

    /**
     * @param Watchlist[]|Collection $watchlistEntries
     */
    public function setWatchlistEntries($watchlistEntries): void
    {
        $this->watchlistEntries = $watchlistEntries;
    }

    /**
     * @return Share[]|Collection
     */
    public function getShares()
    {
        return $this->shares;
    }

    /**
     * @param Share[]|Collection $shares
     */
    public function setShares($shares): void
    {
        $this->shares = $shares;
    }

    /**
     * @return Currency[]|Collection
     */
    public function getCurrencies()
    {
        return $this->currencies;
    }

    /**
     * @param Currency[]|Collection $currencies
     */
    public function setCurrencies($currencies): void
    {
        $this->currencies = $currencies;
    }

    /**
     * @return Sector[]|Collection
     */
    public function getSectors(): array
    {
        return $this->sectors;
    }

    /**
     * @param Sector[]|Collection $sectors
     */
    public function setSectors($sectors): void
    {
        $this->sectors = $sectors;
    }

    /**
     * @return Strategy[]|Collection
     */
    public function getStrategies(): array
    {
        if (null !== $this->strategies) {

            return $this->strategies;
        } else {

            return [];
        }
    }

    /**
     * @param Strategy[]|Collection $strategies
     */
    public function setStrategies($strategies): void
    {
        $this->strategies = $strategies;
    }

    /**
     * @return Label[]|Collection
     */
    public function getLabels(): array
    {
        return $this->labels;
    }

    /**
     * @param Label[]|Collection $labels
     */
    public function setLabels(array $labels): void
    {
        $this->labels = $labels;
    }

}
