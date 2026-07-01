<?php

namespace App\Service;

use App\Entity\Sale;
use App\Repository\BaremeRepository;

/**
 * Computes the prime of a sale from the barème in effect at the sale date.
 */
class PrimeCalculator
{
    public function __construct(private readonly BaremeRepository $baremes)
    {
    }

    /**
     * Returns the prime as a decimal string, or null when no matching barème
     * row applies (unknown product/offer for that date).
     */
    public function compute(Sale $sale): ?string
    {
        $produit = $sale->getType();
        $offre = $sale->getOffre();
        $date = $sale->getDate();

        if (null === $produit || null === $offre || null === $date) {
            return null;
        }

        $bareme = $this->baremes->findEffective($produit, $offre, $date);

        return $bareme?->getPrime();
    }

    /**
     * Computes and stores the prime on the sale.
     */
    public function apply(Sale $sale): void
    {
        $sale->setPrime($this->compute($sale));
    }
}
