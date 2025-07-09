<?php

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;


#[ORM\Entity()]
class LogEntry
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Portfolio::class)]
    #[ORM\JoinColumn(name: 'portfolio_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Portfolio $portfolio;

    #[ORM\Column(name: "action", type: "string", length: 64, unique: false, nullable: false)]
    private string $action;

    #[ORM\Column(name: "result", type: "string", length: 256, unique: false, nullable: false)]
    private string $result;

    #[ORM\Column(name: "failed", type: "boolean", nullable: false)]
    private bool $failed;

    #[Context([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d H:i:s'])]
    #[ORM\Column(name: "date_time", type: "datetime", nullable: false)]
    private DateTime $dateTime;


    public function __construct()
    {
        $this->action = '';
        $this->result = '';
        $this->failed = false;
        $this->dateTime = new DateTime();
    }

    public function __toString()
    {
        return (string) $this->action;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getPortfolio(): ?Portfolio
    {
        return $this->portfolio;
    }

    public function setPortfolio(?Portfolio $portfolio): void
    {
        $this->portfolio = $portfolio;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): void
    {
        $this->action = $action;
    }

    public function getResult(): string
    {
        return $this->result;
    }

    public function setResult(string $result): void
    {
        $this->result = $result;
    }

    public function hasFailed(): bool
    {
        return $this->failed;
    }

    public function setFailed(bool $failed): void
    {
        $this->failed = $failed;
    }

    public function getDateTime(): DateTime
    {
        return $this->dateTime;
    }

    public function setDateTime(DateTime $dateTime): void
    {
        $this->dateTime = $dateTime;
    }

}
