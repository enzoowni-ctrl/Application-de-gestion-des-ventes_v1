<?php

namespace App\Controller;

use App\Entity\Sale;
use App\Entity\User;
use App\Repository\SaleRepository;
use App\Repository\UserRepository;
use App\Service\PrimeCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/sales')]
class SaleController extends AbstractController
{
    public function __construct(
        private readonly SaleRepository $sales,
        private readonly UserRepository $users,
        private readonly EntityManagerInterface $em,
        private readonly PrimeCalculator $primeCalculator,
    ) {
    }

    #[Route('', name: 'sale_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $data = array_map($this->serialize(...), $this->sales->findBy([], ['date' => 'DESC']));

        return $this->json($data);
    }

    #[Route('/{id}', name: 'sale_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse
    {
        $sale = $this->sales->find($id);
        if (!$sale instanceof Sale) {
            return $this->json(['error' => 'Sale not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->serialize($sale));
    }

    #[Route('', name: 'sale_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $payload = $this->decode($request);
        if (!is_array($payload)) {
            return $this->json(['error' => 'Invalid JSON body'], Response::HTTP_BAD_REQUEST);
        }

        $sale = new Sale();
        $sale->setCreatedAt(new \DateTimeImmutable());

        $errors = $this->apply($sale, $payload, true);
        if ($errors) {
            return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $this->primeCalculator->apply($sale);
        $this->em->persist($sale);
        $this->em->flush();

        return $this->json($this->serialize($sale), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'sale_update', methods: ['PUT', 'PATCH'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $sale = $this->sales->find($id);
        if (!$sale instanceof Sale) {
            return $this->json(['error' => 'Sale not found'], Response::HTTP_NOT_FOUND);
        }

        $payload = $this->decode($request);
        if (!is_array($payload)) {
            return $this->json(['error' => 'Invalid JSON body'], Response::HTTP_BAD_REQUEST);
        }

        $errors = $this->apply($sale, $payload, false);
        if ($errors) {
            return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $this->primeCalculator->apply($sale);
        $this->em->flush();

        return $this->json($this->serialize($sale));
    }

    #[Route('/{id}', name: 'sale_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse
    {
        $sale = $this->sales->find($id);
        if (!$sale instanceof Sale) {
            return $this->json(['error' => 'Sale not found'], Response::HTTP_NOT_FOUND);
        }

        $this->em->remove($sale);
        $this->em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return list<string> validation errors
     */
    private function apply(Sale $sale, array $data, bool $isCreate): array
    {
        $errors = [];

        // agent (required on create)
        if (array_key_exists('agent', $data) || $isCreate) {
            $agent = $this->users->find((int) ($data['agent'] ?? 0));
            if (!$agent instanceof User) {
                $errors[] = 'agent: unknown or missing user id';
            } else {
                $sale->setAgent($agent);
            }
        }

        if (array_key_exists('valideParId', $data)) {
            if (null === $data['valideParId']) {
                $sale->setValideParId(null);
            } else {
                $valePar = $this->users->find((int) $data['valideParId']);
                if (!$valePar instanceof User) {
                    $errors[] = 'valideParId: unknown user id';
                } else {
                    $sale->setValideParId($valePar);
                }
            }
        }

        if (array_key_exists('date', $data) || $isCreate) {
            $date = $this->parseDate($data['date'] ?? null);
            if (!$date instanceof \DateTimeInterface) {
                $errors[] = 'date: required, expected format YYYY-MM-DD';
            } else {
                $sale->setDate($date);
            }
        }

        $strings = [
            'nCommande' => 'setNCommande',
            'domaine' => 'setDomaine',
            'type' => 'setType',
            'offre' => 'setOffre',
            'statut' => 'setStatut',
        ];
        foreach ($strings as $field => $setter) {
            if (array_key_exists($field, $data) || $isCreate) {
                $value = $data[$field] ?? null;
                if (!is_string($value) || '' === $value) {
                    $errors[] = sprintf('%s: required non-empty string', $field);
                } else {
                    $sale->{$setter}($value);
                }
            }
        }

        $bools = ['porta' => 'setPorta', 'pto' => 'setPto', 'convergence' => 'setConvergence'];
        foreach ($bools as $field => $setter) {
            if (array_key_exists($field, $data) || $isCreate) {
                $value = $data[$field] ?? null;
                if (!is_bool($value)) {
                    $errors[] = sprintf('%s: required boolean', $field);
                } else {
                    $sale->{$setter}($value);
                }
            }
        }

        if (array_key_exists('valeur', $data) || $isCreate) {
            $value = $data['valeur'] ?? null;
            if (!is_numeric($value)) {
                $errors[] = 'valeur: required number';
            } else {
                $sale->setValeur((string) $value);
            }
        }

        if (array_key_exists('bascule', $data)) {
            $sale->setBascule(null === $data['bascule'] ? null : (string) $data['bascule']);
        }
        if (array_key_exists('pointDeVente', $data)) {
            $sale->setPointDeVente(null === $data['pointDeVente'] ? null : (string) $data['pointDeVente']);
        }
        if (array_key_exists('motifRejet', $data)) {
            $sale->setMotifRejet(null === $data['motifRejet'] ? null : (string) $data['motifRejet']);
        }

        if (array_key_exists('dateValidation', $data)) {
            if (null === $data['dateValidation']) {
                $sale->setDateValidation(null);
            } else {
                $dv = $this->parseDateTime($data['dateValidation']);
                if (!$dv instanceof \DateTimeImmutable) {
                    $errors[] = 'dateValidation: invalid datetime';
                } else {
                    $sale->setDateValidation($dv);
                }
            }
        }

        return $errors;
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(Sale $sale): array
    {
        $agent = $sale->getAgent();
        $valePar = $sale->getValideParId();

        return [
            'id' => $sale->getId(),
            'agent' => $agent ? ['id' => $agent->getId(), 'email' => $agent->getEmail()] : null,
            'date' => $sale->getDate()?->format('Y-m-d'),
            'nCommande' => $sale->getNCommande(),
            'bascule' => $sale->getBascule(),
            'domaine' => $sale->getDomaine(),
            'type' => $sale->getType(),
            'offre' => $sale->getOffre(),
            'porta' => $sale->isPorta(),
            'pto' => $sale->isPto(),
            'convergence' => $sale->isConvergence(),
            'pointDeVente' => $sale->getPointDeVente(),
            'valeur' => $sale->getValeur(),
            'statut' => $sale->getStatut(),
            'motifRejet' => $sale->getMotifRejet(),
            'valideParId' => $valePar ? ['id' => $valePar->getId(), 'email' => $valePar->getEmail()] : null,
            'dateValidation' => $sale->getDateValidation()?->format(\DateTimeInterface::ATOM),
            'createdAt' => $sale->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            'prime' => $sale->getPrime(),
        ];
    }

    private function decode(Request $request): mixed
    {
        return json_decode($request->getContent(), true);
    }

    private function parseDate(mixed $value): ?\DateTimeInterface
    {
        if (!is_string($value) || '' === $value) {
            return null;
        }
        $date = \DateTime::createFromFormat('Y-m-d', $value);

        return $date instanceof \DateTime ? $date : null;
    }

    private function parseDateTime(mixed $value): ?\DateTimeImmutable
    {
        if (!is_string($value) || '' === $value) {
            return null;
        }
        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception) {
            return null;
        }
    }
}
