<?php

namespace App\Command;

use App\Entity\Bareme;
use App\Repository\BaremeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Seeds the prime barème from the Bouygues remuneration grid.
 *
 * Offers with two values switch tariff on 2026-06-08, modelled as two dated
 * rows (the old one ends 2026-06-07, the new one starts 2026-06-08).
 */
#[AsCommand(name: 'app:seed-bareme', description: 'Seed the prime barème (idempotent).')]
class SeedBaremeCommand extends Command
{
    private const CHANGE_DATE = '2026-06-08';
    private const BASE_DATE = '2026-01-01';

    /**
     * [produit, offre, primeAvant, primeApres|null].
     *
     * @var list<array{0: string, 1: string, 2: string, 3: ?string}>
     */
    private const GRID = [
        ['FTTH', 'FIT', '48.38', null],
        ['FTTH', 'Série Spéciale', '101.45', '82.00'],
        ['FTTH', 'MUST', '134.27', '153.27'],
        ['FTTH', 'ULTYM', '153.29', '173.30'],
        ['XGBOX', 'XGBOX', '85.32', null],
        ['FAM', 'FAM 2H | PRO | 5Go', '11.10', null],
        ['FAM', 'FAM SL 20Go', '51.80', null],
        ['FAM', 'FAM SL 130Go', '96.20', null],
        ['FAM', 'FAM 150Go', '122.10', null],
        ['FAM', 'FAM 200Go | 300Go', '170.20', null],
        ['FSM', 'FSM 2H | PRO | 5Go', '8.00', null],
        ['FSM', 'FSM SL 20Go', '14.80', null],
        ['FSM', 'FSM SL 130Go', '33.30', null],
        ['FSM', 'FSM 150Go', '33.30', null],
        ['FSM', 'FSM 200Go | 300Go', '33.30', null],
        ['FAM MIG', 'FAM MIG 2H | PRO | 5Go', '0.71', null],
        ['FAM MIG', 'FAM MIG SL 20Go', '6.70', null],
        ['FAM MIG', 'FAM MIG SL 130Go', '14.80', null],
        ['FAM MIG', 'FAM MIG 150Go', '22.20', null],
        ['FAM MIG', 'FAM MIG 200Go | 300Go', '29.60', null],
        ['BBOX', 'BBOX EXTRA', '37.00', null],
    ];

    public function __construct(
        private readonly BaremeRepository $baremes,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $created = 0;

        foreach (self::GRID as [$produit, $offre, $primeAvant, $primeApres]) {
            if (null === $primeApres) {
                $created += $this->upsert($produit, $offre, $primeAvant, self::BASE_DATE, null);
            } else {
                $created += $this->upsert($produit, $offre, $primeAvant, self::BASE_DATE, '2026-06-07');
                $created += $this->upsert($produit, $offre, $primeApres, self::CHANGE_DATE, null);
            }
        }

        $this->em->flush();
        $io->success(sprintf('Barème seeded (%d row(s) created/updated).', $created));

        return Command::SUCCESS;
    }

    private function upsert(string $produit, string $offre, string $prime, string $dateEffet, ?string $dateFin): int
    {
        $effet = new \DateTime($dateEffet);
        $existing = $this->baremes->findOneBy(['produit' => $produit, 'offre' => $offre, 'dateEffet' => $effet]);
        $bareme = $existing ?? new Bareme();
        $bareme->setProduit($produit);
        $bareme->setOffre($offre);
        $bareme->setPrime($prime);
        $bareme->setDateEffet($effet);
        $bareme->setDateFin(null === $dateFin ? null : new \DateTime($dateFin));
        $bareme->setActif(true);
        $this->em->persist($bareme);

        return null === $existing ? 1 : 0;
    }
}
