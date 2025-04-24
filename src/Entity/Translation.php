<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity()]
class Translation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(name: "keystring", type: "string", length: 255, unique: true, nullable: false)]
    private string $key;

    #[ORM\Column(name: "de", type: "string", length: 255, unique: false, nullable: true)]
    private ?string $de;

    #[ORM\Column(name: "en", type: "string", length: 255, unique: false, nullable: true)]
    private ?string $en;

    #[ORM\Column(name: "fr", type: "string", length: 255, unique: false, nullable: true)]
    private ?string $fr;


    public function __construct()
    {
    }

    public function __toString()
    {
        return (string) $this->key;
    }

    public function getTranslationByLang($lang)
    {
        if (isset($this->$lang)) {
            return $this->$lang;
        }

        return null;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getKey(): ?string
    {
        return $this->key;
    }

    /**
     * @param string $key
     */
    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    /**
     * @return string|null
     */
    public function getDe(): ?string
    {
        return $this->de;
    }

    /**
     * @param string|null $de
     */
    public function setDe(?string $de): void
    {
        $this->de = $de;
    }

    /**
     * @return string|null
     */
    public function getEn(): ?string
    {
        return $this->en;
    }

    /**
     * @param string|null $en
     */
    public function setEn(?string $en): void
    {
        $this->en = $en;
    }

    /**
     * @return string|null
     */
    public function getFr(): ?string
    {
        return $this->fr;
    }

    /**
     * @param string|null $fr
     */
    public function setFr(?string $fr): void
    {
        $this->fr = $fr;
    }

}
