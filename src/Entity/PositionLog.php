<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * PositionLog
 *
 * @ORM\Table(name="position_log")
 * @ORM\Entity
 */
class PositionLog
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
     * @var Position
     * @Serializer\Type("App\Entity\Position")
     *
     * @ORM\ManyToOne(targetEntity="Position")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="position_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $position;

    /**
     * @var \DateTime
     * @Serializer\Type("DateTime<'Y-m-d'>")
     *
     * @ORM\Column(name="date", type="date", nullable=false)
     */
    private $date;

    /**
     * @var string
     * @Serializer\Type("string")
     *
     * @ORM\Column(name="log", type="string", length=256, nullable=false)
     */
    private $log;

    /**
     * @var string|null
     * @Serializer\Type("string")
     *
     * @ORM\Column(name="emoticon", type="string", length=8, nullable=true)
     */
    private $emoticon;

    /**
     * @var boolean
     * @Serializer\Type("boolean")
     *
     * @ORM\Column(name="demo", type="boolean", nullable=false)
     */
    private bool $demo = true;

    /**
     * @var boolean
     * @Serializer\Type("boolean")
     *
     * @ORM\Column(name="pinned", type="boolean", nullable=false)
     */
    private bool $pinned = false;

    /**
     * @var integer
     * @Serializer\Type("integer")
     */
    private $positionId;


    public function __clone()
    {
        $this->id = null;
    }


    public function __toString()
    {
        return 'PositionLogEntry for position: ' . $this->position;
    }


    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Position|null
     */
    public function getPosition(): ?Position
    {
        return $this->position;
    }

    /**
     * @param Position|null $position
     */
    public function setPosition(?Position $position): void
    {
        $this->position = $position;
    }

    /**
     * @return \DateTime|null
     */
    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    /**
     * @param \DateTime $date
     */
    public function setDate(\DateTime $date): void
    {
        $this->date = $date;
    }

    /**
     * @return string|null
     */
    public function getLog(): ?string
    {
        return $this->log;
    }

    /**
     * @param string $log
     */
    public function setLog(string $log): void
    {
        $this->log = $log;
    }

    /**
     * @return string|null
     */
    public function getEmoticon(): ?string
    {
        return $this->emoticon;
    }

    /**
     * @param string|null $emoticon
     */
    public function setEmoticon(?string $emoticon): void
    {
        $this->emoticon = $emoticon;
    }

    public function isDemo(): bool
    {
        return $this->demo;
    }

    public function setDemo(bool $demo): void
    {
        $this->demo = $demo;
    }

    public function isPinned(): bool
    {
        return $this->pinned;
    }

    public function setPinned(bool $pinned): void
    {
        $this->pinned = $pinned;
    }

}
