<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\WorkHours;
use App\Repository\UserRepository;
use App\Repository\WorkHoursRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/work-hours')]
class WorkHoursController extends AbstractController
{
    public function __construct(
        private readonly WorkHoursRepository $workHours,
        private readonly UserRepository $users,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('', name: 'work_hours_index', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $current = $this->getUser();
        if (!$current instanceof User) {
            return $this->json(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $agents = $this->users->findAccessibleAgents($current);
        $agentIds = array_map(static fn (User $u) => $u->getId(), $agents);
        if ([] === $agentIds) {
            return $this->json([]);
        }

        $criteria = ['agent' => $agentIds];
        $periode = $request->query->get('periode');
        if (is_string($periode) && '' !== $periode) {
            $criteria['periode'] = $periode;
        }

        return $this->json(array_map($this->serialize(...), $this->workHours->findBy($criteria)));
    }

    #[Route('', name: 'work_hours_upsert', methods: ['POST', 'PUT'])]
    public function upsert(Request $request): JsonResponse
    {
        $current = $this->getUser();
        if (!$current instanceof User) {
            return $this->json(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }
        if (!$this->isGranted('ROLE_SUPERVISEUR') && !$this->isGranted('ROLE_CHEF_PLATEAU')) {
            return $this->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid JSON body'], Response::HTTP_BAD_REQUEST);
        }

        $errors = [];
        $agent = $this->users->find((int) ($data['agent'] ?? 0));
        if (!$agent instanceof User) {
            $errors[] = 'agent: unknown or missing user id';
        }
        $periode = $data['periode'] ?? null;
        if (!is_string($periode) || 1 !== preg_match('/^\d{4}-\d{2}$/', $periode)) {
            $errors[] = 'periode: required, expected format YYYY-MM';
        }
        $heures = $data['heures'] ?? null;
        if (!is_numeric($heures) || (float) $heures < 0) {
            $errors[] = 'heures: required positive number';
        }
        if ($errors) {
            return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        // Supervisors may only record hours for their own agents.
        $accessible = $this->users->findAccessibleAgents($current);
        if (!in_array($agent, $accessible, true)) {
            return $this->json(['error' => 'agent: not in your scope'], Response::HTTP_FORBIDDEN);
        }

        $entry = $this->workHours->findOneBy(['agent' => $agent, 'periode' => $periode]) ?? new WorkHours();
        $entry->setAgent($agent);
        $entry->setPeriode($periode);
        $entry->setHeures((string) $heures);
        $entry->setSaisiPar($current);
        $entry->setUpdatedAt(new \DateTimeImmutable());

        $this->em->persist($entry);
        $this->em->flush();

        return $this->json($this->serialize($entry), Response::HTTP_CREATED);
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(WorkHours $w): array
    {
        $agent = $w->getAgent();
        $saisiPar = $w->getSaisiPar();

        return [
            'id' => $w->getId(),
            'agent' => $agent ? ['id' => $agent->getId(), 'email' => $agent->getEmail()] : null,
            'periode' => $w->getPeriode(),
            'heures' => $w->getHeures(),
            'saisiPar' => $saisiPar ? ['id' => $saisiPar->getId(), 'email' => $saisiPar->getEmail()] : null,
            'updatedAt' => $w->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }
}
