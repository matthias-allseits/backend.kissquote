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
     * @var BankAccount
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\BankAccount")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="bank_account_id", referencedColumnName="id")
     * })
     */
    private $bankAccount;

    /**
     * @var Share
     *
     * @ORM\ManyToOne(targetEntity="Share")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="share_id", referencedColumnName="id")
     * })
     */
    private $share;

    /**
     * @var Currency
     *
     * @ORM\ManyToOne(targetEntity="Currency")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="currency_id", referencedColumnName="id")
     * })
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

// todo: add annotations
//    private $transactions;

// todo: add annotations
//    private $rates;


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
     * @param BankAccount $bankAccount
     */
    public function setBankAccount(BankAccount $bankAccount): void
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
     * @return DateTime
     */
    public function getActiveFrom(): DateTime
    {
        return $this->activeFrom;
    }

    /**
     * @param DateTime $activeFrom
     */
    public function setActiveFrom(DateTime $activeFrom): void
    {
        $this->activeFrom = $activeFrom;
    }

    /**
     * @return DateTime
     */
    public function getActiveUntil(): DateTime
    {
        return $this->activeUntil;
    }

    /**
     * @param DateTime $activeUntil
     */
    public function setActiveUntil(DateTime $activeUntil): void
    {
        $this->activeUntil = $activeUntil;
    }

}
