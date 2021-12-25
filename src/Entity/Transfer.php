<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


/**
 * Transfer
 *
 * @ORM\Table(name="transfer")
 * @ORM\Entity
 */
class Transfer
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
     * @ORM\ManyToOne(targetEntity="BankAccount")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="bank_account_from_id", referencedColumnName="id")
     * })
     */
    private $bankAccountFrom;

    /**
     * @var BankAccount
     *
     * @ORM\ManyToOne(targetEntity="BankAccount")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="bank_account_to_id", referencedColumnName="id")
     * })
     */
    private $bankAccountTo;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="date", nullable=false)
     */
    private $date;

    /**
     * @var float
     *
     * @ORM\Column(name="amount", type="float", precision=10, scale=0, nullable=false)
     */
    private $amount;



    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): void
    {
        $this->date = $date;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): void
    {
        $this->amount = $amount;
    }

    /**
     * @return BankAccount
     */
    public function getBankAccountFrom(): BankAccount
    {
        return $this->bankAccountFrom;
    }

    /**
     * @param BankAccount $bankAccountFrom
     */
    public function setBankAccountFrom(BankAccount $bankAccountFrom): void
    {
        $this->bankAccountFrom = $bankAccountFrom;
    }

    /**
     * @return BankAccount
     */
    public function getBankAccountTo(): BankAccount
    {
        return $this->bankAccountTo;
    }

    /**
     * @param BankAccount $bankAccountTo
     */
    public function setBankAccountTo(BankAccount $bankAccountTo): void
    {
        $this->bankAccountTo = $bankAccountTo;
    }

}
