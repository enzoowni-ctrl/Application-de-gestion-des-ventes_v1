<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/users')]
class UserController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $hasher,
    ) {
    }

    #[Route('', name: 'user_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json(array_map($this->serialize(...), $this->users->findAll()));
    }

    #[Route('/my-team', name: 'users_my_team', methods: ['GET'])]
    public function myTeam(): JsonResponse
    {
        $current = $this->getUser();
        if (!$current instanceof User) {
            return $this->json(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $agents = $this->users->findBy(
            ['manager' => $current],
            ['email' => 'ASC']
        );

        return $this->json(array_map($this->serialize(...), $agents));
    }

    #[Route('/{id}', name: 'user_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse
    {
        $user = $this->users->find($id);
        if (!$user instanceof User) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->serialize($user));
    }

    #[Route('/{id}', name: 'user_update', methods: ['PUT', 'PATCH'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request): JsonResponse
    {
        if (!$this->isGranted('ROLE_CHEF_PLATEAU')) {
            return $this->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $user = $this->users->find($id);
        if (!$user instanceof User) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid JSON body'], Response::HTTP_BAD_REQUEST);
        }

        if (array_key_exists('roles', $data)) {
            if (!is_array($data['roles'])) {
                return $this->json(['errors' => ['roles: expected an array']], Response::HTTP_BAD_REQUEST);
            }
            $user->setRoles(array_values(array_filter($data['roles'], 'is_string')));
        }

        if (array_key_exists('manager', $data)) {
            if (null === $data['manager']) {
                $user->setManager(null);
            } else {
                $manager = $this->users->find((int) $data['manager']);
                if (!$manager instanceof User) {
                    return $this->json(['errors' => ['manager: unknown user id']], Response::HTTP_BAD_REQUEST);
                }
                $user->setManager($manager);
            }
        }

        $this->em->flush();

        return $this->json($this->serialize($user));
    }

    #[Route('', name: 'user_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $current = $this->getUser();
        if (!$current instanceof User) {
            return $this->json(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid JSON body'], Response::HTTP_BAD_REQUEST);
        }

        $email    = $data['email'] ?? null;
        $password = $data['password'] ?? null;
        $nom      = $data['nom'] ?? null;
        $logAdmcc = $data['logAdmcc'] ?? null;

        $errors = [];
        if (!is_string($nom) || trim($nom) === '') {
            $errors[] = 'nom: requis';
        }
        if (!is_string($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'email: requis et doit être valide';
        } elseif ($this->users->findOneBy(['email' => $email])) {
            $errors[] = 'email: déjà utilisé';
        }
        if (!is_string($password) || strlen($password) < 6) {
            $errors[] = 'password: requis, min 6 caractères';
        }
        if ($errors) {
            return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        // Si c'est un sup/admin qui crée, l'agent lui est rattaché
        $roles = $current->getRoles();
        $isSupOrAdmin = in_array('ROLE_SUPERVISEUR', $roles) 
                     || in_array('ROLE_ADMIN', $roles)
                     || in_array('ROLE_CHEF_PLATEAU', $roles);

        $user = new User();
        $user->setEmail($email);
        $user->setNom($nom);
        $user->setLogAdmcc($logAdmcc);
        $user->setRoles(['ROLE_AGENT']);
        $user->setPassword($this->hasher->hashPassword($user, $password));

        if ($isSupOrAdmin) {
            $user->setManager($current);
        }

        $this->em->persist($user);
        $this->em->flush();

        return $this->json($this->serialize($user), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'user_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse
    {
        $current = $this->getUser();
        if (!$current instanceof User) {
            return $this->json(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $agent = $this->users->find($id);
        if (!$agent instanceof User) {
            return $this->json(['error' => 'Agent introuvable'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier que l'agent appartient bien au superviseur connecté
        if ($agent->getManager()?->getId() !== $current->getId()) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $this->em->remove($agent);
        $this->em->flush();

        return $this->json(['success' => true]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(User $user): array
    {
        $manager = $user->getManager();

        return [
            'id'       => $user->getId(),
            'nom'      => $user->getNom(),
            'email'    => $user->getEmail(),
            'logAdmcc' => $user->getLogAdmcc(),
            'roles'    => $user->getRoles(),
            'manager'  => $manager ? ['id' => $manager->getId(), 'email' => $manager->getEmail()] : null,
        ];
    }
}