<?php

namespace App\Controller;

use App\Entity\Bareme;
use App\Repository\BaremeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/baremes')]
class BaremeController extends AbstractController
{
    public function __construct(
        private readonly BaremeRepository $baremes,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('', name: 'bareme_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $data = array_map($this->serialize(...), $this->baremes->findBy([], ['produit' => 'ASC', 'offre' => 'ASC', 'dateEffet' => 'DESC']));

        return $this->json($data);
    }

    #[Route('', name: 'bareme_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return $this->json(['error' => 'Invalid JSON body'], Response::HTTP_BAD_REQUEST);
        }

        $bareme = new Bareme();
        $errors = $this->apply($bareme, $payload, true);
        if ($errors) {
            return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $this->em->persist($bareme);
        $this->em->flush();

        return $this->json($this->serialize($bareme), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'bareme_update', methods: ['PUT', 'PATCH'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $bareme = $this->baremes->find($id);
        if (!$bareme instanceof Bareme) {
            return $this->json(['error' => 'Bareme not found'], Response::HTTP_NOT_FOUND);
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return $this->json(['error' => 'Invalid JSON body'], Response::HTTP_BAD_REQUEST);
        }

        $errors = $this->apply($bareme, $payload, false);
        if ($errors) {
            return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $this->em->flush();

        return $this->json($this->serialize($bareme));
    }

    #[Route('/{id}', name: 'bareme_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse
    {
        $bareme = $this->baremes->find($id);
        if (!$bareme instanceof Bareme) {
            return $this->json(['error' => 'Bareme not found'], Response::HTTP_NOT_FOUND);
        }

        $this->em->remove($bareme);
        $this->em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return list<string> validation errors
     */
    private function apply(Bareme $bareme, array $data, bool $isCreate): array
    {
        $errors = [];

        $strings = ['produit' => 'setProduit', 'offre' => 'setOffre'];
        foreach ($strings as $field => $setter) {
            if (array_key_exists($field, $data) || $isCreate) {
                $value = $data[$field] ?? null;
                if (!is_string($value) || '' === $value) {
                    $errors[] = sprintf('%s: required non-empty string', $field);
                } else {
                    $bareme->{$setter}($value);
                }
            }
        }

        if (array_key_exists('prime', $data) || $isCreate) {
            $value = $data['prime'] ?? null;
            if (!is_numeric($value)) {
                $errors[] = 'prime: required number';
            } else {
                $bareme->setPrime((string) $value);
            }
        }

        if (array_key_exists('dateEffet', $data) || $isCreate) {
            $date = $this->parseDate($data['dateEffet'] ?? null);
            if (!$date instanceof \DateTimeInterface) {
                $errors[] = 'dateEffet: required, expected format YYYY-MM-DD';
            } else {
                $bareme->setDateEffet($date);
            }
        }

        if (array_key_exists('dateFin', $data)) {
            if (null === $data['dateFin'] || '' === $data['dateFin']) {
                $bareme->setDateFin(null);
            } else {
                $date = $this->parseDate($data['dateFin']);
                if (!$date instanceof \DateTimeInterface) {
                    $errors[] = 'dateFin: expected format YYYY-MM-DD';
                } else {
                    $bareme->setDateFin($date);
                }
            }
        }

        if (array_key_exists('actif', $data)) {
            $bareme->setActif((bool) $data['actif']);
        }

        return $errors;
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(Bareme $bareme): array
    {
        return [
            'id' => $bareme->getId(),
            'produit' => $bareme->getProduit(),
            'offre' => $bareme->getOffre(),
            'prime' => $bareme->getPrime(),
            'dateEffet' => $bareme->getDateEffet()?->format('Y-m-d'),
            'dateFin' => $bareme->getDateFin()?->format('Y-m-d'),
            'actif' => $bareme->isActif(),
        ];
    }

    private function parseDate(mixed $value): ?\DateTimeInterface
    {
        if (!is_string($value) || '' === $value) {
            return null;
        }
        $date = \DateTime::createFromFormat('Y-m-d', $value);

        return $date instanceof \DateTime ? $date : null;
    }
}
