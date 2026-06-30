<?php

namespace App\Entity;

use App\Repository\WorkHoursRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WorkHoursRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_AGENT_PERIODE', fields: ['agent', 'periode'])]
class WorkHours
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $agent = null;

    /** Period in YYYY-MM (monthly granularity). */
    #[ORM\Column(length: 7)]
    private ?string $periode = null;

    #[ORM\Column(type: 'decimal', precision: 6, scale: 2)]
    private ?string $heures = null;

    #[ORM\ManyToOne]
    private ?User $saisiPar = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

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

    public function getPeriode(): ?string
    {
        return $this->periode;
    }

    public function setPeriode(string $periode): static
    {
        $this->periode = $periode;

        return $this;
    }

    public function getHeures(): ?string
    {
        return $this->heures;
    }

    public function setHeures(string $heures): static
    {
        $this->heures = $heures;

        return $this;
    }

    public function getSaisiPar(): ?User
    {
        return $this->saisiPar;
    }

    public function setSaisiPar(?User $saisiPar): static
    {
        $this->saisiPar = $saisiPar;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
