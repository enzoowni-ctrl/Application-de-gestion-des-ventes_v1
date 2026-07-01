<?php

namespace App\Controller;

use App\Entity\Bareme;
use App\Entity\Sale;
use App\Entity\User;
use App\Entity\WorkHours;
use App\Repository\BaremeRepository;
use App\Repository\SaleRepository;
use App\Repository\UserRepository;
use App\Repository\WorkHoursRepository;
use App\Service\PrimeCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class WebController extends AbstractController
{
    /** Products and their offers, mirrored from the seeded barème. */
    private const OFFERS = [
        'FTTH' => ['FIT', 'Série Spéciale', 'MUST', 'ULTYM'],
        'XGBOX' => ['XGBOX'],
        'FAM' => ['FAM 2H | PRO | 5Go', 'FAM SL 20Go', 'FAM SL 130Go', 'FAM 150Go', 'FAM 200Go | 300Go'],
        'FSM' => ['FSM 2H | PRO | 5Go', 'FSM SL 20Go', 'FSM SL 130Go', 'FSM 150Go', 'FSM 200Go | 300Go'],
        'BBOX' => ['BBOX EXTRA'],
    ];

    public function __construct(
        private readonly SaleRepository $sales,
        private readonly UserRepository $users,
        private readonly BaremeRepository $baremes,
        private readonly WorkHoursRepository $workHours,
        private readonly EntityManagerInterface $em,
        private readonly PrimeCalculator $primeCalculator,
    ) {
    }

    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser() instanceof User) {
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): never
    {
        throw new \LogicException('Intercepted by the logout firewall.');
    }

    #[Route('/', name: 'app_dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        $current = $this->currentUser();
        $agents = $this->users->findAccessibleAgents($current);
        $agentIds = array_map(static fn (User $u) => (int) $u->getId(), $agents);
        $periode = (new \DateTimeImmutable())->format('Y-m');

        $aggregates = $this->sales->aggregateForAgents($agentIds, $periode);
        $totalPrimes = 0.0;
        $totalCa = 0.0;
        $totalVentes = 0;
        foreach ($aggregates as $agg) {
            $totalPrimes += $agg['primes'];
            $totalCa += $agg['ca'];
            $totalVentes += $agg['ventes'];
        }

        $pending = $agentIds ? $this->sales->count(['agent' => $agentIds, 'statut' => 'Brute']) : 0;

        $ranking = [];
        foreach ($agents as $agent) {
            $agg = $aggregates[(int) $agent->getId()] ?? null;
            if ($agg) {
                $ranking[] = ['agent' => $agent, 'primes' => $agg['primes']];
            }
        }
        usort($ranking, static fn ($a, $b) => $b['primes'] <=> $a['primes']);

        return $this->render('dashboard.html.twig', [
            'pending' => $pending,
            'total_ventes' => $totalVentes,
            'total_primes' => $totalPrimes,
            'total_ca' => $totalCa,
            'nb_agents' => count($agents),
            'periode' => $periode,
            'ranking' => array_slice($ranking, 0, 5),
        ]);
    }

    #[Route('/ventes', name: 'app_sales', methods: ['GET'])]
    public function sales(): Response
    {
        $current = $this->currentUser();
        $agents = $this->users->findAccessibleAgents($current);
        $agentIds = array_map(static fn (User $u) => (int) $u->getId(), $agents);
        $sales = $agentIds ? $this->sales->findBy(['agent' => $agentIds], ['date' => 'DESC']) : [];

        return $this->render('sales.html.twig', ['sales' => $sales]);
    }

    #[Route('/ventes/nouvelle', name: 'app_sale_new', methods: ['GET', 'POST'])]
    public function newSale(Request $request): Response
    {
        $current = $this->currentUser();

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('sale_new', (string) $request->request->get('_token'))) {
                $this->addFlash('error', 'Jeton de sécurité invalide.');

                return $this->redirectToRoute('app_sale_new');
            }

            $sale = new Sale();
            $sale->setCreatedAt(new \DateTimeImmutable());
            $sale->setAgent($current);

            $dateStr = (string) $request->request->get('date');
            $date = \DateTime::createFromFormat('Y-m-d', $dateStr);
            $valeur = (string) $request->request->get('valeur');
            $errors = [];
            if (!$date instanceof \DateTime) {
                $errors[] = 'Date invalide.';
            }
            if (!is_numeric($valeur)) {
                $errors[] = 'Valeur invalide.';
            }
            foreach (['nCommande', 'domaine', 'type', 'offre'] as $f) {
                if ('' === trim((string) $request->request->get($f))) {
                    $errors[] = ucfirst($f).' requis.';
                }
            }

            if ($errors) {
                foreach ($errors as $e) {
                    $this->addFlash('error', $e);
                }

                return $this->redirectToRoute('app_sale_new');
            }

            $sale->setDate($date);
            $sale->setNCommande((string) $request->request->get('nCommande'));
            $sale->setBascule(($b = trim((string) $request->request->get('bascule'))) !== '' ? $b : null);
            $sale->setDomaine((string) $request->request->get('domaine'));
            $sale->setType((string) $request->request->get('type'));
            $sale->setOffre((string) $request->request->get('offre'));
            $sale->setPorta($request->request->getBoolean('porta'));
            $sale->setPto($request->request->getBoolean('pto'));
            $sale->setConvergence($request->request->getBoolean('convergence'));
            $sale->setPointDeVente(($p = trim((string) $request->request->get('pointDeVente'))) !== '' ? $p : null);
            $sale->setValeur($valeur);
            $sale->setStatut('Brute');

            $this->primeCalculator->apply($sale);
            $this->em->persist($sale);
            $this->em->flush();

            $this->addFlash('success', 'Vente enregistrée (statut Brute, en attente de validation).');

            return $this->redirectToRoute('app_sales');
        }

        return $this->render('sale_new.html.twig', [
            'offers' => self::OFFERS,
            'today' => (new \DateTimeImmutable())->format('Y-m-d'),
        ]);
    }

    #[Route('/validation', name: 'app_validation', methods: ['GET'])]
    #[IsGranted('ROLE_SUPERVISEUR')]
    public function validation(): Response
    {
        $current = $this->currentUser();
        $agents = $this->users->findAccessibleAgents($current);
        $agentIds = array_map(static fn (User $u) => (int) $u->getId(), $agents);
        $sales = $agentIds ? $this->sales->findBy(['agent' => $agentIds, 'statut' => 'Brute'], ['date' => 'DESC']) : [];

        return $this->render('validation.html.twig', ['sales' => $sales]);
    }

    #[Route('/ventes/{id}/valider', name: 'app_sale_validate', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_SUPERVISEUR')]
    public function validateSale(int $id, Request $request): Response
    {
        return $this->decideSale($id, $request, 'Nette');
    }

    #[Route('/ventes/{id}/rejeter', name: 'app_sale_reject', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_SUPERVISEUR')]
    public function rejectSale(int $id, Request $request): Response
    {
        return $this->decideSale($id, $request, 'Rejetée');
    }

    #[Route('/resultats', name: 'app_results', methods: ['GET'])]
    public function results(Request $request): Response
    {
        $current = $this->currentUser();
        $periode = (string) $request->query->get('periode', '');
        if (1 !== preg_match('/^\d{4}-\d{2}$/', $periode)) {
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
            $rows[] = [
                'agent' => $agent,
                'ca' => $agg['ca'],
                'primes' => $agg['primes'],
                'ventes' => $agg['ventes'],
                'heures' => $heures,
                'caParHeure' => $heures > 0 ? round($agg['ca'] / $heures, 2) : null,
            ];
        }

        return $this->render('results.html.twig', [
            'rows' => $rows,
            'periode' => $periode,
            'can_edit' => $this->isGranted('ROLE_SUPERVISEUR'),
        ]);
    }

    #[Route('/heures', name: 'app_hours_save', methods: ['POST'])]
    #[IsGranted('ROLE_SUPERVISEUR')]
    public function saveHours(Request $request): Response
    {
        $current = $this->currentUser();
        $periode = (string) $request->request->get('periode');
        if (!$this->isCsrfTokenValid('hours_save', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $this->redirectToRoute('app_results', ['periode' => $periode]);
        }

        $agent = $this->users->find((int) $request->request->get('agent'));
        $heures = (string) $request->request->get('heures');

        if (1 !== preg_match('/^\d{4}-\d{2}$/', $periode) || !$agent instanceof User || !is_numeric($heures) || (float) $heures < 0) {
            $this->addFlash('error', 'Saisie des heures invalide.');

            return $this->redirectToRoute('app_results', ['periode' => $periode]);
        }

        if (!in_array($agent, $this->users->findAccessibleAgents($current), true)) {
            throw $this->createAccessDeniedException('Agent hors de votre périmètre.');
        }

        $entry = $this->workHours->findOneBy(['agent' => $agent, 'periode' => $periode]) ?? new WorkHours();
        $entry->setAgent($agent);
        $entry->setPeriode($periode);
        $entry->setHeures($heures);
        $entry->setSaisiPar($current);
        $entry->setUpdatedAt(new \DateTimeImmutable());
        $this->em->persist($entry);
        $this->em->flush();

        $this->addFlash('success', 'Heures enregistrées.');

        return $this->redirectToRoute('app_results', ['periode' => $periode]);
    }

    #[Route('/baremes', name: 'app_baremes', methods: ['GET'])]
    public function baremes(): Response
    {
        $rows = $this->baremes->findBy([], ['produit' => 'ASC', 'offre' => 'ASC', 'dateEffet' => 'DESC']);

        return $this->render('baremes.html.twig', [
            'baremes' => $rows,
            'offers' => self::OFFERS,
            'can_edit' => $this->isGranted('ROLE_SUPERVISEUR'),
        ]);
    }

    #[Route('/baremes/nouveau', name: 'app_bareme_new', methods: ['POST'])]
    #[IsGranted('ROLE_SUPERVISEUR')]
    public function newBareme(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('bareme_new', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $this->redirectToRoute('app_baremes');
        }

        $produit = (string) $request->request->get('produit');
        $offre = (string) $request->request->get('offre');
        $prime = (string) $request->request->get('prime');
        $dateEffet = \DateTime::createFromFormat('Y-m-d', (string) $request->request->get('dateEffet'));
        $dateFinStr = trim((string) $request->request->get('dateFin'));

        if ('' === $produit || '' === $offre || !is_numeric($prime) || !$dateEffet instanceof \DateTime) {
            $this->addFlash('error', 'Barème invalide (produit, offre, prime, date d\'effet requis).');

            return $this->redirectToRoute('app_baremes');
        }

        $bareme = new Bareme();
        $bareme->setProduit($produit);
        $bareme->setOffre($offre);
        $bareme->setPrime($prime);
        $bareme->setDateEffet($dateEffet);
        if ('' !== $dateFinStr && ($df = \DateTime::createFromFormat('Y-m-d', $dateFinStr)) instanceof \DateTime) {
            $bareme->setDateFin($df);
        }
        $bareme->setActif(true);
        $this->em->persist($bareme);
        $this->em->flush();

        $this->addFlash('success', 'Barème ajouté.');

        return $this->redirectToRoute('app_baremes');
    }

    #[Route('/baremes/{id}/toggle', name: 'app_bareme_toggle', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_SUPERVISEUR')]
    public function toggleBareme(int $id, Request $request): Response
    {
        $bareme = $this->baremes->find($id);
        if ($bareme instanceof Bareme && $this->isCsrfTokenValid('bareme_toggle'.$id, (string) $request->request->get('_token'))) {
            $bareme->setActif(!$bareme->isActif());
            $this->em->flush();
        }

        return $this->redirectToRoute('app_baremes');
    }

    #[Route('/baremes/{id}/prime', name: 'app_bareme_prime', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_SUPERVISEUR')]
    public function updateBaremePrime(int $id, Request $request): Response
    {
        $bareme = $this->baremes->find($id);
        $prime = (string) $request->request->get('prime');
        if ($bareme instanceof Bareme && is_numeric($prime) && $this->isCsrfTokenValid('bareme_prime'.$id, (string) $request->request->get('_token'))) {
            $bareme->setPrime($prime);
            $this->em->flush();
            $this->addFlash('success', 'Prime mise à jour.');
        }

        return $this->redirectToRoute('app_baremes');
    }

    private function decideSale(int $id, Request $request, string $statut): Response
    {
        $current = $this->currentUser();
        $sale = $this->sales->find($id);
        if (!$sale instanceof Sale) {
            throw $this->createNotFoundException();
        }
        if (!$this->isCsrfTokenValid('sale_decide'.$id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $this->redirectToRoute('app_validation');
        }
        if (!in_array($sale->getAgent(), $this->users->findAccessibleAgents($current), true)) {
            throw $this->createAccessDeniedException('Vente hors de votre périmètre.');
        }

        $sale->setStatut($statut);
        $sale->setValideParId($current);
        $sale->setDateValidation(new \DateTimeImmutable());
        if ('Rejetée' === $statut) {
            $sale->setMotifRejet(($m = trim((string) $request->request->get('motifRejet'))) !== '' ? $m : 'Non précisé');
        }
        $this->primeCalculator->apply($sale);
        $this->em->flush();

        $this->addFlash('success', 'Nette' === $statut ? 'Vente validée.' : 'Vente rejetée.');

        return $this->redirectToRoute('app_validation');
    }

    private function currentUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }
}
