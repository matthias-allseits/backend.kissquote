<?php

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;


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

    /**
     * @Serializer\Type("string")
     */
    #[ORM\Column(name: "action", type: "string", length: 64, unique: false, nullable: false)]
    private string $action;

    /**
     * @Serializer\Type("string")
     */
    #[ORM\Column(name: "result", type: "string", length: 256, unique: false, nullable: false)]
    private string $result;

    /**
     * @Serializer\Type("boolean")
     */
    #[ORM\Column(name: "failed", type: "boolean", nullable: false)]
    private bool $failed;

    /**
     * @Serializer\Type("DateTime<'Y-m-d H:i:s'>")
     * @Serializer\SerializedName("dateTime")
     */
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

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Portfolio|null
     */
    public function getPortfolio(): ?Portfolio
    {
        return $this->portfolio;
    }

    /**
     * @param Portfolio|null $portfolio
     */
    public function setPortfolio(?Portfolio $portfolio): void
    {
        $this->portfolio = $portfolio;
    }

    /**
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * @param string $action
     */
    public function setAction(string $action): void
    {
        $this->action = $action;
    }

    /**
     * @return string
     */
    public function getResult(): string
    {
        return $this->result;
    }

    /**
     * @param string $result
     */
    public function setResult(string $result): void
    {
        $this->result = $result;
    }

    /**
     * @return bool
     */
    public function hasFailed(): bool
    {
        return $this->failed;
    }

    /**
     * @param bool $failed
     */
    public function setFailed(bool $failed): void
    {
        $this->failed = $failed;
    }

    /**
     * @return DateTime
     */
    public function getDateTime(): DateTime
    {
        return $this->dateTime;
    }

    /**
     * @param DateTime $dateTime
     */
    public function setDateTime(DateTime $dateTime): void
    {
        $this->dateTime = $dateTime;
    }

}
