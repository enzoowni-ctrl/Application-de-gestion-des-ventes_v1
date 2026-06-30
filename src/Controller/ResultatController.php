<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\SaleRepository;
use App\Repository\UserRepository;
use App\Repository\WorkHoursRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/results')]
class ResultatController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly SaleRepository $sales,
        private readonly WorkHoursRepository $workHours,
    ) {
    }

    /**
     * Per-agent results for a month: CA (sum of validated sale values), total
     * primes, hours worked, and the objective CA / hour. Scoped to the agents
     * the caller can see (own / supervised / all for chef de plateau).
     */
    #[Route('', name: 'results_index', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $current = $this->getUser();
        if (!$current instanceof User) {
            return $this->json(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $periode = $request->query->get('periode');
        if (!is_string($periode) || 1 !== preg_match('/^\d{4}-\d{2}$/', $periode)) {
            $periode = (new \DateTimeImmutable())->format('Y-m');
        }

        $agents = $this->users->findAccessibleAgents($current);
        $agentIds = array_map(static fn (User $u) => (int) $u->getId(), $agents);

        $aggregates = $this->sales->aggregateForAgents($agentIds, $periode);

        $hoursByAgent = [];
        foreach ($this->workHours->findBy(['agent' => $agentIds, 'periode' => $periode]) as $w) {
            $hoursByAgent[(int) $w->getAgent()?->getId()] = (float) $w->getHeures();
        }

        $rows = [];
        foreach ($agents as $agent) {
            $id = (int) $agent->getId();
            $agg = $aggregates[$id] ?? ['ca' => 0.0, 'primes' => 0.0, 'ventes' => 0];
            $heures = $hoursByAgent[$id] ?? 0.0;
            $caParHeure = $heures > 0 ? round($agg['ca'] / $heures, 2) : null;

            $rows[] = [
                'agent' => ['id' => $id, 'email' => $agent->getEmail()],
                'periode' => $periode,
                'ca' => round($agg['ca'], 2),
                'primes' => round($agg['primes'], 2),
                'ventes' => $agg['ventes'],
                'heures' => $heures,
                'caParHeure' => $caParHeure,
            ];
        }

        return $this->json($rows);
    }
}
