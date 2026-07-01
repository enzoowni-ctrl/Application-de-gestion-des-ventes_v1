<?php

namespace App\Repository;

use App\Entity\Bareme;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Bareme>
 */
class BaremeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Bareme::class);
    }

    /**
     * Returns the active barème row matching the given product/offer in effect
     * at the given date (latest dateEffet <= date, dateFin null or >= date).
     */
    public function findEffective(string $produit, string $offre, \DateTimeInterface $date): ?Bareme
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.produit = :produit')
            ->andWhere('b.offre = :offre')
            ->andWhere('b.actif = true')
            ->andWhere('b.dateEffet <= :date')
            ->andWhere('b.dateFin IS NULL OR b.dateFin >= :date')
            ->setParameter('produit', $produit)
            ->setParameter('offre', $offre)
            ->setParameter('date', $date->format('Y-m-d'))
            ->orderBy('b.dateEffet', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
