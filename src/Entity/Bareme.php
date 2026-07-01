<?php

namespace App\Entity;

use App\Repository\BaremeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BaremeRepository::class)]
class Bareme
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $produit = null;

    #[ORM\Column(length: 255)]
    private ?string $offre = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private ?string $prime = null;

    #[ORM\Column(type: 'date')]
    private ?\DateTimeInterface $dateEffet = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column]
    private bool $actif = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProduit(): ?string
    {
        return $this->produit;
    }

    public function setProduit(string $produit): static
    {
        $this->produit = $produit;

        return $this;
    }

    public function getOffre(): ?string
    {
        return $this->offre;
    }

    public function setOffre(string $offre): static
    {
        $this->offre = $offre;

        return $this;
    }

    public function getPrime(): ?string
    {
        return $this->prime;
    }

    public function setPrime(string $prime): static
    {
        $this->prime = $prime;

        return $this;
    }

    public function getDateEffet(): ?\DateTimeInterface
    {
        return $this->dateEffet;
    }

    public function setDateEffet(\DateTimeInterface $dateEffet): static
    {
        $this->dateEffet = $dateEffet;

        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTimeInterface $dateFin): static
    {
        $this->dateFin = $dateFin;

        return $this;
    }

    public function isActif(): bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;

        return $this;
    }
}
