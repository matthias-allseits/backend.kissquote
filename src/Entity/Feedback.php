<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;


/**
 * Currency
 *
 * @ORM\Table(name="feedback")
 * @ORM\Entity
 */
class Feedback
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
     * @var Portfolio|null
     *
     * @ORM\ManyToOne(targetEntity="Portfolio")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="portfolio_id", referencedColumnName="id", nullable=true)
     * })
     */
    private $portfolio;

    /**
     * @var string
     * @Serializer\Type("string")
     *
     * @ORM\Column(name="mood", type="string", length=64, nullable=false)
     */
    private $mood;

    /**
     * @var string
     * @Serializer\Type("string")
     *
     * @ORM\Column(name="feedback", type="text", nullable=true)
     */
    private $feedback;


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
    public function getMood(): ?string
    {
        return $this->mood;
    }

    /**
     * @param string $mood
     */
    public function setMood(string $mood): void
    {
        $this->mood = $mood;
    }

    /**
     * @return string
     */
    public function getFeedback(): ?string
    {
        return $this->feedback;
    }

    /**
     * @param string $feedback
     */
    public function setFeedback(string $feedback): void
    {
        $this->feedback = $feedback;
    }

}
