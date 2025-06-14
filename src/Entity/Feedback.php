<?php

namespace App\Entity;

use DateTime;use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;


#[ORM\Entity()]
class Feedback
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Portfolio::class)]
    #[ORM\JoinColumn(name: 'portfolio_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private $portfolio;

    #[ORM\Column(name: "mood", type: "string", length: 64, unique: false, nullable: false)]
    private string $mood;

    #[ORM\Column(name: "feedback", type: "text", nullable: true)]
    private ?string $feedback;

    #[Context([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d H:i:s'])]
    #[ORM\Column(name: "date_time", type: "datetime", nullable: false)]
    private DateTime $dateTime;


    public function __construct()
    {
        $this->dateTime = new DateTime();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPortfolio(): ?Portfolio
    {
        return $this->portfolio;
    }

    public function setPortfolio(?Portfolio $portfolio): void
    {
        $this->portfolio = $portfolio;
    }

    public function getMood(): ?string
    {
        return $this->mood;
    }

    public function setMood(string $mood): void
    {
        $this->mood = $mood;
    }

    public function getFeedback(): ?string
    {
        return $this->feedback;
    }

    public function setFeedback(string $feedback): void
    {
        $this->feedback = $feedback;
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
