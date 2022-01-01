<?php

namespace App\Entity;

use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;


/**
 * Portfolio
 *
 * @ORM\Table(name="portfolio")
 * @ORM\Entity
 */
class Portfolio
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
     * @var string|null
     *
     * @ORM\Column(name="user_name", type="string", length=32, nullable=true, unique=true)
     */
    private $userName;

    /**
     * @var string|null
     *
     * @ORM\Column(name="hash_key", type="string", length=32, nullable=true, unique=true)
     */
    private $hashKey;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="start_date", type="date", nullable=false)
     */
    private $startDate;

    /**
     * @var Collection|BankAccount[]
     *
     * @ORM\OneToMany(targetEntity="App\Entity\BankAccount", mappedBy="portfolio", cascade={"remove"})
     */
    private $bankAccounts;

    /**
     * @var Collection|Share[]
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Share", mappedBy="portfolio")
     */
    private $shares;

    /**
     * @var Collection|Currency[]
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Currency", mappedBy="portfolio")
     */
    private $currencies;


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
        foreach($this->bankAccounts as $account) {
            $positions = array_merge($positions, $account->getPositions()->toArray());
        }

        return $positions;
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


    public function getShareByIsin(string $isin): ?Share
    {
        $hit = null;
        foreach ($this->shares as $share) {
            if ($share->getIsin() == $isin) {
                $hit = $share;
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


    /**
     * @return int
     */
    public function getId(): int
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
     * @return Collection|BankAccount[]
     */
    public function getBankAccounts(): Collection
    {
        return $this->bankAccounts;
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

}
