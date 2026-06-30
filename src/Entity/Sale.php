<?php

namespace App\Entity;

use App\Repository\SaleRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SaleRepository::class)]
class Sale
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'sales')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $agent = null;

    #[ORM\Column(type: 'date')]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(length: 50)]
    private ?string $nCommande = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $bascule = null;

    #[ORM\Column(length: 20)]
    private ?string $domaine = null;

    #[ORM\Column(length: 50)]
    private ?string $type = null;

    #[ORM\Column(length: 255)]
    private ?string $offre = null;

    #[ORM\Column]
    private ?bool $porta = null;

    #[ORM\Column]
    private ?bool $pto = null;

    #[ORM\Column]
    private ?bool $convergence = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pointDeVente = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private ?string $valeur = null;

    #[ORM\Column(length: 20)]
    private ?string $statut = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $motifRejet = null;

    #[ORM\ManyToOne]
    private ?User $valideParId = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $dateValidation = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * Prime computed from the barème in effect at the sale date.
     */
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $prime = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAgent(): ?User
    {
        return $this->agent;
    }

    public function setAgent(?User $agent): static
    {
        $this->agent = $agent;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getNCommande(): ?string
    {
        return $this->nCommande;
    }

    public function setNCommande(string $nCommande): static
    {
        $this->nCommande = $nCommande;

        return $this;
    }

    public function getBascule(): ?string
    {
        return $this->bascule;
    }

    public function setBascule(?string $bascule): static
    {
        $this->bascule = $bascule;

        return $this;
    }

    public function getDomaine(): ?string
    {
        return $this->domaine;
    }

    public function setDomaine(string $domaine): static
    {
        $this->domaine = $domaine;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

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

    public function isPorta(): ?bool
    {
        return $this->porta;
    }

    public function setPorta(bool $porta): static
    {
        $this->porta = $porta;

        return $this;
    }

    public function isPto(): ?bool
    {
        return $this->pto;
    }

    public function setPto(bool $pto): static
    {
        $this->pto = $pto;

        return $this;
    }

    public function isConvergence(): ?bool
    {
        return $this->convergence;
    }

    public function setConvergence(bool $convergence): static
    {
        $this->convergence = $convergence;

        return $this;
    }

    public function getPointDeVente(): ?string
    {
        return $this->pointDeVente;
    }

    public function setPointDeVente(?string $pointDeVente): static
    {
        $this->pointDeVente = $pointDeVente;

        return $this;
    }

    public function getValeur(): ?string
    {
        return $this->valeur;
    }

    public function setValeur(string $valeur): static
    {
        $this->valeur = $valeur;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getMotifRejet(): ?string
    {
        return $this->motifRejet;
    }

    public function setMotifRejet(?string $motifRejet): static
    {
        $this->motifRejet = $motifRejet;

        return $this;
    }

    public function getValideParId(): ?User
    {
        return $this->valideParId;
    }

    public function setValideParId(?User $valideParId): static
    {
        $this->valideParId = $valideParId;

        return $this;
    }

    public function getDateValidation(): ?\DateTimeImmutable
    {
        return $this->dateValidation;
    }

    public function setDateValidation(?\DateTimeImmutable $dateValidation): static
    {
        $this->dateValidation = $dateValidation;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getPrime(): ?string
    {
        return $this->prime;
    }

    public function setPrime(?string $prime): static
    {
        $this->prime = $prime;

        return $this;
    }
}
