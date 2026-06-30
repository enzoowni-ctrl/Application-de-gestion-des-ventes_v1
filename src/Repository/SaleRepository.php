<?php

namespace App\Repository;

use App\Entity\Sale;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Sale>
 */
class SaleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sale::class);
    }

    /**
     * Aggregates validated ("Nette") sales for the given agents in a month.
     *
     * @param list<int> $agentIds
     * @param string    $periode   YYYY-MM
     *
     * @return array<int, array{ca: float, primes: float, ventes: int}> keyed by agent id
     */
    public function aggregateForAgents(array $agentIds, string $periode): array
    {
        if ([] === $agentIds) {
            return [];
        }

        $start = \DateTime::createFromFormat('Y-m-d', $periode.'-01');
        if (!$start instanceof \DateTime) {
            return [];
        }
        $end = (clone $start)->modify('last day of this month');

        $rows = $this->createQueryBuilder('s')
            ->select('IDENTITY(s.agent) AS agentId', 'SUM(s.valeur) AS ca', 'SUM(s.prime) AS primes', 'COUNT(s.id) AS ventes')
            ->andWhere('s.agent IN (:agents)')
            ->andWhere('s.statut = :statut')
            ->andWhere('s.date BETWEEN :start AND :end')
            ->setParameter('agents', $agentIds)
            ->setParameter('statut', 'Nette')
            ->setParameter('start', $start->format('Y-m-d'))
            ->setParameter('end', $end->format('Y-m-d'))
            ->groupBy('s.agent')
            ->getQuery()
            ->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['agentId']] = [
                'ca' => (float) $row['ca'],
                'primes' => (float) $row['primes'],
                'ventes' => (int) $row['ventes'],
            ];
        }

        return $result;
    }
}
