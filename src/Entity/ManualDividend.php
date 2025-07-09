<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Ignore;

#[ORM\Entity()]
#[ORM\Index(name: "shareindex", columns: ["share_id"])]
class ManualDividend
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[Ignore]
    #[ORM\ManyToOne(targetEntity: Share::class, inversedBy: "manualDividends")]
    #[ORM\JoinColumn(name: 'share_id', referencedColumnName: 'id', nullable: false)]
	private Share $share;

    #[ORM\Column(name: "year", type: "integer", nullable: false)]
    private int $year;

    #[ORM\Column(name: "amount", type: "integer", nullable: false)]
    private int $amount;


    public function __toString()
    {
        return $this->share->getName() . ' has a manual-dividend of ' . $this->amount . ' for year ' . $this->year;
    }


    public function getId(): int
    {
        return $this->id;
    }

    public function getShare(): ?Share
    {
        return $this->share;
    }

    public function setShare(Share $share): void
    {
        $this->share = $share;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(int $year): void
    {
        $this->year = $year;
    }

    public function getAmount(): ?int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): void
    {
        $this->amount = $amount;
    }

}
