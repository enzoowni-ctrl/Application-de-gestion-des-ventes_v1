import { useState, useEffect, createContext, useContext } from "react";
import type { ReactNode } from "react";
import {
  listSales, listUsers, createUser, createSale, updateSale,
  displayName, type ApiSale,
} from "./api";
import {
  BarChart, Bar, LineChart, Line, PieChart, Pie, Cell,
  XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer,
} from "recharts";
import {
  LayoutDashboard, Plus, List, CheckSquare, BarChart2, Settings,
  LogOut, Bell, ChevronDown, Search, Download, X, Check, Eye,
  TrendingUp, Users, Clock, Euro, AlertCircle, Edit2,
  ShoppingCart, FileText, ArrowUpRight,
} from "lucide-react";

// ─── Types ────────────────────────────────────────────────────────────────────

type Role = "agent" | "superviseur";
type Status = "Brute" | "Nette" | "Rejetée";
type Product = "FTTH" | "XGBOX" | "FAM" | "FSM" | "BBOX";
type View =
  | "login"
  | "agent-dashboard"
  | "agent-nouvelle-vente"
  | "agent-mes-ventes"
  | "sup-dashboard"
  | "sup-validation"
  | "sup-primes"
  | "sup-baremes";

// ─── Static Data ──────────────────────────────────────────────────────────────

const OFFERS: Record<Product, string[]> = {
  FTTH:  ["Série Spéciale", "MUST", "ULTYM", "ESSENTIELLE"],
  XGBOX: ["XGBOX Start", "XGBOX Pro", "XGBOX Elite"],
  FAM:   ["FAM Initiale", "FAM Premium"],
  FSM:   ["FSM Basic", "FSM Plus"],
  BBOX:  ["BBOX Fit", "BBOX Must", "BBOX Ultym"],
};

const PRODUCTS: Product[] = ["FTTH", "XGBOX", "FAM", "FSM", "BBOX"];

const PRODUCT_COLORS: Record<Product, string> = {
  FTTH:  "#1D4ED8",
  XGBOX: "#7C3AED",
  FAM:   "#059669",
  FSM:   "#D97706",
  BBOX:  "#0891B2",
};

const ALL_SALES = [
  { id:1,  date:"2026-06-28", type:"FTTH",  offre:"ULTYM",         numCommande:"CMD-2026-0482", numBascule:"BAS-1847", domaine:"FAI",    porta:true,  pto:false, convergence:true,  valeur:149.99, pointVente:"Paris 11e",   statut:"Brute",   prime:45, agent:"Sophie Martin" },
  { id:2,  date:"2026-06-27", type:"XGBOX", offre:"XGBOX Pro",     numCommande:"CMD-2026-0481", numBascule:"BAS-1846", domaine:"Mobile", porta:false, pto:true,  convergence:false, valeur:39.99,  pointVente:"Lyon Centre",  statut:"Nette",   prime:30, agent:"Lucas Bernard" },
  { id:3,  date:"2026-06-26", type:"BBOX",  offre:"BBOX Must",     numCommande:"CMD-2026-0480", numBascule:"BAS-1845", domaine:"FAI",    porta:true,  pto:true,  convergence:true,  valeur:29.99,  pointVente:"Marseille",    statut:"Rejetée", prime:0,  agent:"Emma Durand" },
  { id:4,  date:"2026-06-25", type:"FTTH",  offre:"MUST",          numCommande:"CMD-2026-0479", numBascule:"BAS-1844", domaine:"FAI",    porta:false, pto:false, convergence:false, valeur:39.99,  pointVente:"Bordeaux",     statut:"Nette",   prime:35, agent:"Sophie Martin" },
  { id:5,  date:"2026-06-24", type:"FAM",   offre:"FAM Premium",   numCommande:"CMD-2026-0478", numBascule:"BAS-1843", domaine:"Mobile", porta:true,  pto:false, convergence:true,  valeur:19.99,  pointVente:"Nantes",       statut:"Brute",   prime:25, agent:"Thomas Petit" },
  { id:6,  date:"2026-06-23", type:"FSM",   offre:"FSM Plus",      numCommande:"CMD-2026-0477", numBascule:"BAS-1842", domaine:"Mobile", porta:false, pto:true,  convergence:false, valeur:9.99,   pointVente:"Lille",        statut:"Nette",   prime:15, agent:"Léa Rousseau" },
  { id:7,  date:"2026-06-22", type:"FTTH",  offre:"Série Spéciale",numCommande:"CMD-2026-0476", numBascule:"BAS-1841", domaine:"FAI",    porta:true,  pto:true,  convergence:true,  valeur:24.99,  pointVente:"Paris 11e",    statut:"Nette",   prime:40, agent:"Sophie Martin" },
  { id:8,  date:"2026-06-21", type:"XGBOX", offre:"XGBOX Elite",   numCommande:"CMD-2026-0475", numBascule:"BAS-1840", domaine:"Mobile", porta:false, pto:false, convergence:false, valeur:59.99,  pointVente:"Lyon Centre",  statut:"Brute",   prime:50, agent:"Lucas Bernard" },
  { id:9,  date:"2026-06-20", type:"BBOX",  offre:"BBOX Ultym",    numCommande:"CMD-2026-0474", numBascule:"BAS-1839", domaine:"FAI",    porta:true,  pto:false, convergence:true,  valeur:49.99,  pointVente:"Paris 8e",     statut:"Nette",   prime:20, agent:"Emma Durand" },
  { id:10, date:"2026-06-19", type:"FAM",   offre:"FAM Initiale",  numCommande:"CMD-2026-0473", numBascule:"BAS-1838", domaine:"Mobile", porta:false, pto:true,  convergence:false, valeur:9.99,   pointVente:"Strasbourg",   statut:"Brute",   prime:18, agent:"Thomas Petit" },
  { id:11, date:"2026-06-18", type:"FTTH",  offre:"ULTYM",         numCommande:"CMD-2026-0472", numBascule:"BAS-1837", domaine:"FAI",    porta:true,  pto:false, convergence:true,  valeur:149.99, pointVente:"Nice",         statut:"Nette",   prime:45, agent:"Sophie Martin" },
  { id:12, date:"2026-06-17", type:"BBOX",  offre:"BBOX Fit",      numCommande:"CMD-2026-0471", numBascule:"BAS-1836", domaine:"FAI",    porta:false, pto:false, convergence:false, valeur:19.99,  pointVente:"Toulouse",     statut:"Brute",   prime:15, agent:"Léa Rousseau" },
];

const WEEKLY_PRIMES = [
  { semaine: "S22", primes: 120 },
  { semaine: "S23", primes: 185 },
  { semaine: "S24", primes: 95 },
  { semaine: "S25", primes: 210 },
  { semaine: "S26", primes: 165 },
];

const PRODUCT_DIST = [
  { name:"FTTH",  value:38 },
  { name:"XGBOX", value:22 },
  { name:"BBOX",  value:18 },
  { name:"FAM",   value:14 },
  { name:"FSM",   value:8  },
];

const AGENT_PRIMES = [
  { agent:"Sophie Martin", FTTH:120, XGBOX:0,  FAM:0,  FSM:0,  BBOX:0  },
  { agent:"Lucas Bernard", FTTH:0,   XGBOX:80, FAM:0,  FSM:0,  BBOX:0  },
  { agent:"Emma Durand",   FTTH:45,  XGBOX:30, FAM:0,  FSM:0,  BBOX:20 },
  { agent:"Thomas Petit",  FTTH:35,  XGBOX:0,  FAM:43, FSM:0,  BBOX:40 },
  { agent:"Léa Rousseau",  FTTH:0,   XGBOX:50, FAM:0,  FSM:15, BBOX:0  },
];

const BAREMES = [
  { id:1,  produit:"FTTH",  offre:"ULTYM",          prime:45, dateEffet:"2026-01-01", actif:true  },
  { id:2,  produit:"FTTH",  offre:"MUST",            prime:35, dateEffet:"2026-01-01", actif:true  },
  { id:3,  produit:"FTTH",  offre:"Série Spéciale",  prime:40, dateEffet:"2026-01-01", actif:true  },
  { id:4,  produit:"FTTH",  offre:"ESSENTIELLE",     prime:25, dateEffet:"2026-01-01", actif:true  },
  { id:5,  produit:"XGBOX", offre:"XGBOX Start",     prime:20, dateEffet:"2026-01-01", actif:true  },
  { id:6,  produit:"XGBOX", offre:"XGBOX Pro",       prime:30, dateEffet:"2026-01-01", actif:true  },
  { id:7,  produit:"XGBOX", offre:"XGBOX Elite",     prime:50, dateEffet:"2026-01-01", actif:true  },
  { id:8,  produit:"BBOX",  offre:"BBOX Fit",         prime:15, dateEffet:"2026-01-01", actif:true  },
  { id:9,  produit:"BBOX",  offre:"BBOX Must",        prime:20, dateEffet:"2026-01-01", actif:true  },
  { id:10, produit:"BBOX",  offre:"BBOX Ultym",       prime:20, dateEffet:"2026-03-01", actif:true  },
  { id:11, produit:"BBOX",  offre:"BBOX Ultym",       prime:15, dateEffet:"2026-01-01", actif:false },
  { id:12, produit:"FAM",   offre:"FAM Initiale",     prime:18, dateEffet:"2026-01-01", actif:true  },
  { id:13, produit:"FAM",   offre:"FAM Premium",      prime:25, dateEffet:"2026-01-01", actif:true  },
  { id:14, produit:"FSM",   offre:"FSM Basic",        prime:10, dateEffet:"2026-01-01", actif:true  },
  { id:15, produit:"FSM",   offre:"FSM Plus",         prime:15, dateEffet:"2026-01-01", actif:true  },
];

// ─── Sales data (API-backed) ──────────────────────────────────────────────────

export interface Sale {
  id: number;
  date: string;
  type: string;
  offre: string;
  numCommande: string;
  numBascule: string;
  domaine: string;
  porta: boolean;
  pto: boolean;
  convergence: boolean;
  valeur: number;
  pointVente: string;
  statut: Status;
  prime: number;
  agent: string;
  agentId: number | null;
}

export interface NewSaleInput {
  date: string;
  type: string;
  offre: string;
  numCommande: string;
  numBascule: string;
  domaine: string;
  porta: boolean;
  pto: boolean;
  convergence: boolean;
  valeur: number;
  pointVente: string;
}

const computePrime = (type: string, offre: string): number => {
  const b = BAREMES.find(x => x.actif && x.produit === type && x.offre === offre);
  return b ? b.prime : 0;
};

const fromApi = (s: ApiSale): Sale => ({
  id: s.id,
  date: (s.date ?? "").slice(0, 10),
  type: s.type,
  offre: s.offre,
  numCommande: s.nCommande,
  numBascule: s.bascule ?? "",
  domaine: s.domaine,
  porta: s.porta,
  pto: s.pto,
  convergence: s.convergence,
  valeur: Number(s.valeur),
  pointVente: s.pointDeVente ?? "",
  statut: s.statut as Status,
  prime: computePrime(s.type, s.offre),
  agent: displayName(s.agent?.email),
  agentId: s.agent?.id ?? null,
});

interface SalesContextValue {
  sales: Sale[];
  loading: boolean;
  error: string | null;
  currentAgentId: number | null;
  reload: () => Promise<void>;
  addSale: (input: NewSaleInput) => Promise<void>;
  validateSale: (id: number) => Promise<void>;
  rejectSale: (id: number, motif: string) => Promise<void>;
}

const SalesContext = createContext<SalesContextValue | null>(null);

const useSales = (): SalesContextValue => {
  const ctx = useContext(SalesContext);
  if (!ctx) throw new Error("useSales must be used within SalesProvider");
  return ctx;
};

const SalesProvider = ({ children }: { children: ReactNode }) => {
  const [sales, setSales] = useState<Sale[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [currentAgentId, setCurrentAgentId] = useState<number | null>(null);

  const reload = async () => {
    setLoading(true);
    setError(null);
    try {
      let users = await listUsers();
      if (users.length === 0) {
        const u = await createUser({
          email: "agent@bouyguestelecom.fr",
          password: "password",
          roles: ["ROLE_USER"],
        });
        users = [u];
      }
      setCurrentAgentId(users[0].id);
      const data = await listSales();
      setSales(data.map(fromApi));
    } catch (e) {
      setError(e instanceof Error ? e.message : "Erreur de chargement");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    reload();
  }, []);

  const upsert = (s: ApiSale) =>
    setSales(prev => {
      const mapped = fromApi(s);
      const i = prev.findIndex(x => x.id === mapped.id);
      if (i === -1) return [mapped, ...prev];
      const next = [...prev];
      next[i] = mapped;
      return next;
    });

  const addSale = async (input: NewSaleInput) => {
    if (currentAgentId == null) throw new Error("Aucun agent disponible");
    const created = await createSale({
      agent: currentAgentId,
      date: input.date,
      nCommande: input.numCommande,
      bascule: input.numBascule || null,
      domaine: input.domaine,
      type: input.type,
      offre: input.offre,
      porta: input.porta,
      pto: input.pto,
      convergence: input.convergence,
      pointDeVente: input.pointVente || null,
      valeur: input.valeur,
      statut: "Brute",
    });
    upsert(created);
  };

  const validateSale = async (id: number) => {
    const updated = await updateSale(id, {
      statut: "Nette",
      valideParId: currentAgentId,
      dateValidation: new Date().toISOString(),
    });
    upsert(updated);
  };

  const rejectSale = async (id: number, motif: string) => {
    const updated = await updateSale(id, {
      statut: "Rejetée",
      motifRejet: motif,
      valideParId: currentAgentId,
      dateValidation: new Date().toISOString(),
    });
    upsert(updated);
  };

  return (
    <SalesContext.Provider
      value={{ sales, loading, error, currentAgentId, reload, addSale, validateSale, rejectSale }}
    >
      {children}
    </SalesContext.Provider>
  );
};

// ─── Shared Components ────────────────────────────────────────────────────────

const StatusBadge = ({ status }: { status: Status }) => {
  const cfg = {
    Brute:   { bg:"bg-amber-100",   text:"text-amber-700",   dot:"bg-amber-500"   },
    Nette:   { bg:"bg-emerald-100", text:"text-emerald-700", dot:"bg-emerald-500" },
    Rejetée: { bg:"bg-red-100",     text:"text-red-700",     dot:"bg-red-500"     },
  }[status];
  return (
    <span className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold ${cfg.bg} ${cfg.text}`}>
      <span className={`w-1.5 h-1.5 rounded-full ${cfg.dot}`} />
      {status}
    </span>
  );
};

const StatCard = ({
  icon: Icon, label, value, sub, accent, large,
}: {
  icon: React.ElementType; label: string; value: string; sub?: string;
  accent: string; large?: boolean;
}) => (
  <div className={`bg-white rounded-2xl p-6 border border-black/[0.06] hover:shadow-md transition-shadow ${large ? "border-2 border-amber-200" : ""}`}>
    <div className="flex items-start justify-between mb-4">
      <div className={`w-10 h-10 rounded-xl flex items-center justify-center ${accent}`}>
        <Icon size={18} className="text-white" />
      </div>
      <ArrowUpRight size={14} className="text-emerald-400" />
    </div>
    <div className={`font-bold text-[#0F2056] mb-0.5 ${large ? "text-4xl" : "text-2xl"}`}>{value}</div>
    <div className="text-sm text-slate-500">{label}</div>
    {sub && <div className="text-xs text-slate-400 mt-0.5">{sub}</div>}
  </div>
);

const Toggle = ({ label, value, onChange }: { label: string; value: boolean; onChange: (v: boolean) => void }) => (
  <div className="flex items-center justify-between py-0.5">
    <span className="text-sm font-medium text-slate-700">{label}</span>
    <button
      type="button"
      onClick={() => onChange(!value)}
      style={{ lineHeight: 0, padding: 0 }}
      className={`relative flex-shrink-0 w-11 h-6 rounded-full transition-colors duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-400 ${value ? "bg-[#1D4ED8]" : "bg-slate-200"}`}
    >
      <span
        className={`absolute top-1 left-0 w-4 h-4 bg-white rounded-full shadow-sm transition-transform duration-200 ${value ? "translate-x-6" : "translate-x-1"}`}
      />
    </button>
  </div>
);

// ─── Sidebar ──────────────────────────────────────────────────────────────────

const Sidebar = ({
  role, view, onNavigate, onLogout,
}: {
  role: Role; view: View; onNavigate: (v: View) => void; onLogout: () => void;
}) => {
  const { sales } = useSales();
  const pendingCount = sales.filter(s => s.statut === "Brute").length;

  const agentNav = [
    { view: "agent-dashboard" as View,       icon: LayoutDashboard, label: "Tableau de bord" },
    { view: "agent-nouvelle-vente" as View,  icon: Plus,            label: "Nouvelle vente"  },
    { view: "agent-mes-ventes" as View,      icon: List,            label: "Mes ventes"      },
  ];
  const supNav = [
    { view: "sup-dashboard" as View,  icon: LayoutDashboard, label: "Tableau de bord",     badge: null         },
    { view: "sup-validation" as View, icon: CheckSquare,     label: "File de validation",  badge: pendingCount },
    { view: "sup-primes" as View,     icon: BarChart2,       label: "Tableau des primes",  badge: null         },
    { view: "sup-baremes" as View,    icon: Settings,        label: "Gestion barèmes",     badge: null         },
  ];
  const navItems = role === "agent" ? agentNav : supNav;

  return (
    <aside className="fixed left-0 top-0 h-full w-60 bg-[#0F2056] flex flex-col z-20 select-none">
      {/* Logo */}
      <div className="px-5 py-5 border-b border-white/10">
        <div className="flex items-center gap-3">
          <div className="w-9 h-9 rounded-xl bg-[#1D4ED8] flex items-center justify-center flex-shrink-0 shadow-lg shadow-blue-900/40">
            <span className="text-white font-bold text-sm tracking-tight">BT</span>
          </div>
          <div>
            <div className="text-white font-bold text-sm leading-tight tracking-tight">GestPrimes</div>
            <div className="text-white/40 text-[11px]">Bouygues Telecom</div>
          </div>
        </div>
      </div>

      {/* Role label */}
      <div className="px-5 pt-5 pb-2">
        <span className="text-[10px] font-bold text-white/30 uppercase tracking-widest">
          {role === "agent" ? "Agent Commercial" : "Superviseur"}
        </span>
      </div>

      {/* Nav */}
      <nav className="flex-1 px-3 space-y-0.5">
        {navItems.map(item => {
          const active = view === item.view;
          const b = (item as any).badge;
          return (
            <button
              key={item.view}
              onClick={() => onNavigate(item.view)}
              className={`w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all ${
                active
                  ? "bg-[#1D4ED8] text-white shadow-md shadow-blue-900/30"
                  : "text-white/55 hover:text-white hover:bg-white/[0.08]"
              }`}
            >
              <item.icon size={17} />
              <span className="flex-1 text-left">{item.label}</span>
              {b != null && b > 0 && (
                <span className="bg-amber-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full min-w-[18px] text-center">
                  {b}
                </span>
              )}
            </button>
          );
        })}
      </nav>

      {/* User */}
      <div className="px-4 pb-5">
        <div className="border-t border-white/10 pt-4">
          <div className="flex items-center gap-3 mb-3 px-1">
            <div className="w-8 h-8 rounded-full bg-[#1D4ED8] flex items-center justify-center flex-shrink-0">
              <span className="text-white text-xs font-bold">SM</span>
            </div>
            <div className="flex-1 min-w-0">
              <div className="text-white text-sm font-semibold truncate">Sophie Martin</div>
              <div className="text-white/35 text-[11px] truncate">sophie.martin@bt.fr</div>
            </div>
          </div>
          <button
            onClick={onLogout}
            className="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-white/40 hover:text-white hover:bg-white/[0.08] text-sm transition-all"
          >
            <LogOut size={15} />
            Déconnexion
          </button>
        </div>
      </div>
    </aside>
  );
};

// ─── Header ───────────────────────────────────────────────────────────────────

const Header = ({ title, subtitle }: { title: string; subtitle?: string }) => (
  <header className="fixed top-0 left-60 right-0 h-15 bg-white border-b border-black/[0.06] flex items-center px-8 z-10" style={{ height: 60 }}>
    <div className="flex-1">
      <h1 className="text-base font-bold text-[#0F2056] leading-tight">{title}</h1>
      {subtitle && <p className="text-xs text-slate-400 leading-tight">{subtitle}</p>}
    </div>
    <div className="flex items-center gap-2">
      <button className="relative w-9 h-9 rounded-xl hover:bg-slate-100 flex items-center justify-center transition-colors">
        <Bell size={17} className="text-slate-500" />
        <span className="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full border-2 border-white" />
      </button>
      <div className="flex items-center gap-2 pl-2 ml-1 border-l border-slate-100">
        <div className="w-8 h-8 rounded-full bg-[#1D4ED8] flex items-center justify-center">
          <span className="text-white text-xs font-bold">SM</span>
        </div>
        <ChevronDown size={13} className="text-slate-400" />
      </div>
    </div>
  </header>
);

// ─── LOGIN ────────────────────────────────────────────────────────────────────

const LoginScreen = ({ onLogin }: { onLogin: (r: Role) => void }) => {
  const [role, setRole] = useState<Role>("agent");
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");

  return (
    <div className="min-h-screen flex" style={{ fontFamily: "'Inter', sans-serif" }}>
      {/* Left panel */}
      <div className="hidden lg:flex flex-col justify-between w-2/5 bg-[#0F2056] p-12">
        <div className="flex items-center gap-3">
          <div className="w-10 h-10 rounded-xl bg-[#1D4ED8] flex items-center justify-center shadow-lg shadow-blue-900/40">
            <span className="text-white font-bold text-sm">BT</span>
          </div>
          <div>
            <div className="text-white font-bold text-sm">GestPrimes</div>
            <div className="text-white/40 text-xs">Bouygues Telecom</div>
          </div>
        </div>

        <div>
          <div className="text-white/20 text-7xl font-bold leading-none mb-6">"</div>
          <p className="text-white text-xl font-semibold leading-relaxed mb-4">
            Gérez vos ventes FTTH, XGBOX, BBOX, FAM et FSM avec précision.
          </p>
          <p className="text-white/50 text-sm leading-relaxed">
            Saisie simplifiée, validation rapide, calcul automatique des primes — tout en un seul espace.
          </p>
        </div>

        <div className="flex gap-3">
          {PRODUCTS.map(p => (
            <div key={p} className="px-3 py-1.5 rounded-lg bg-white/10 text-white/60 text-xs font-semibold">{p}</div>
          ))}
        </div>
      </div>

      {/* Right panel */}
      <div className="flex-1 flex items-center justify-center bg-slate-50 p-8">
        <div className="w-full max-w-sm">
          <div className="text-center mb-8">
            <div className="w-14 h-14 rounded-2xl bg-[#1D4ED8] flex items-center justify-center mx-auto mb-4 shadow-lg shadow-blue-200">
              <span className="text-white font-bold text-xl">BT</span>
            </div>
            <h1 className="text-2xl font-bold text-[#0F2056]">Connexion</h1>
            <p className="text-slate-400 text-sm mt-1">Accès agents &amp; superviseurs</p>
          </div>

          {/* Role selector */}
          <div className="flex bg-slate-100 rounded-xl p-1 mb-6">
            {(["agent", "superviseur"] as Role[]).map(r => (
              <button
                key={r}
                onClick={() => setRole(r)}
                className={`flex-1 py-2 rounded-lg text-sm font-semibold transition-all ${
                  role === r ? "bg-white text-[#1D4ED8] shadow-sm" : "text-slate-400 hover:text-slate-600"
                }`}
              >
                {r === "agent" ? "Agent" : "Superviseur"}
              </button>
            ))}
          </div>

          <div className="space-y-4">
            <div>
              <label className="block text-sm font-semibold text-slate-700 mb-1.5">Adresse email</label>
              <input
                type="email"
                value={email}
                onChange={e => setEmail(e.target.value)}
                placeholder="prenom.nom@bouyguestelecom.fr"
                className="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-[#1D4ED8] transition-all"
              />
            </div>
            <div>
              <label className="block text-sm font-semibold text-slate-700 mb-1.5">Mot de passe</label>
              <input
                type="password"
                value={password}
                onChange={e => setPassword(e.target.value)}
                placeholder="••••••••"
                className="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-[#1D4ED8] transition-all"
              />
            </div>
            <button
              onClick={() => onLogin(role)}
              className="w-full bg-[#1D4ED8] hover:bg-[#1E40AF] text-white py-3 rounded-xl font-semibold text-sm transition-colors shadow-md shadow-blue-200 mt-2"
            >
              Se connecter
            </button>
          </div>

          <p className="text-center text-xs text-slate-400 mt-6">
            En vous connectant, vous acceptez les conditions d'utilisation internes Bouygues Telecom.
          </p>
        </div>
      </div>
    </div>
  );
};

// ─── AGENT DASHBOARD ──────────────────────────────────────────────────────────

const AgentDashboard = () => {
  const { sales } = useSales();
  return (
  <div className="p-8 space-y-7">
    <div className="grid grid-cols-4 gap-5">
      <StatCard icon={ShoppingCart} label="Ventes du mois"  value="23"      sub="Juin 2026"      accent="bg-[#1D4ED8]"  />
      <StatCard icon={Clock}        label="En attente"      value="4"       sub="À valider"       accent="bg-amber-500"  />
      <StatCard icon={Check}        label="Validées"        value="17"      sub="Ce mois"         accent="bg-emerald-500"/>
      <StatCard icon={Euro}         label="Primes du mois"  value="775 €"   sub="+12% vs mai"     accent="bg-violet-500" />
    </div>

    <div className="grid grid-cols-5 gap-6">
      {/* Weekly bar chart */}
      <div className="col-span-3 bg-white rounded-2xl p-6 border border-black/[0.06]">
        <div className="flex items-center justify-between mb-5">
          <div>
            <h3 className="font-bold text-[#0F2056] text-sm">Évolution des primes</h3>
            <p className="text-xs text-slate-400">Par semaine — Juin 2026</p>
          </div>
          <span className="text-xs bg-blue-50 text-[#1D4ED8] font-semibold px-2.5 py-1 rounded-lg">Mensuel</span>
        </div>
        <ResponsiveContainer width="100%" height={200}>
          <BarChart data={WEEKLY_PRIMES} barSize={36}>
            <CartesianGrid strokeDasharray="3 3" stroke="#F1F5F9" vertical={false} />
            <XAxis dataKey="semaine" tick={{ fontSize: 11, fill: "#94A3B8" }} axisLine={false} tickLine={false} />
            <YAxis tick={{ fontSize: 11, fill: "#94A3B8" }} axisLine={false} tickLine={false} unit=" €" />
            <Tooltip
              formatter={(v: any) => [`${v} €`, "Prime"]}
              contentStyle={{ borderRadius: 12, border: "none", boxShadow: "0 4px 24px rgba(0,0,0,0.10)", fontSize: 12 }}
            />
            <Bar dataKey="primes" fill="#1D4ED8" radius={[6, 6, 0, 0]} />
          </BarChart>
        </ResponsiveContainer>
      </div>

      {/* Product breakdown */}
      <div className="col-span-2 bg-white rounded-2xl p-6 border border-black/[0.06]">
        <h3 className="font-bold text-[#0F2056] text-sm mb-1">Répartition produits</h3>
        <p className="text-xs text-slate-400 mb-5">Ventes du mois en cours</p>
        <div className="space-y-3.5">
          {PRODUCT_DIST.map(p => (
            <div key={p.name}>
              <div className="flex justify-between text-xs mb-1.5">
                <span className="font-semibold text-slate-700">{p.name}</span>
                <span className="text-slate-400 font-mono">{p.value}%</span>
              </div>
              <div className="h-2 bg-slate-100 rounded-full overflow-hidden">
                <div
                  className="h-full rounded-full transition-all"
                  style={{ width: `${p.value}%`, backgroundColor: PRODUCT_COLORS[p.name as Product] }}
                />
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>

    {/* Recent sales */}
    <div className="bg-white rounded-2xl border border-black/[0.06] overflow-hidden">
      <div className="flex items-center justify-between px-6 py-4 border-b border-slate-100">
        <h3 className="font-bold text-[#0F2056] text-sm">Dernières ventes saisies</h3>
        <span className="text-xs text-slate-400">5 entrées récentes</span>
      </div>
      <table className="w-full">
        <thead>
          <tr className="text-[11px] text-slate-400 font-bold uppercase tracking-wider">
            <th className="text-left px-6 py-3">Date</th>
            <th className="text-left px-6 py-3">Produit</th>
            <th className="text-left px-6 py-3">Offre</th>
            <th className="text-left px-6 py-3">N° Commande</th>
            <th className="text-left px-6 py-3">Statut</th>
            <th className="text-right px-6 py-3">Prime</th>
          </tr>
        </thead>
        <tbody>
          {sales.slice(0, 5).map(s => (
            <tr key={s.id} className="border-t border-slate-50 hover:bg-slate-50/60 transition-colors">
              <td className="px-6 py-3.5 text-sm font-mono text-slate-500">{s.date}</td>
              <td className="px-6 py-3.5">
                <span className="text-sm font-bold text-[#0F2056]">{s.type}</span>
              </td>
              <td className="px-6 py-3.5 text-sm text-slate-600">{s.offre}</td>
              <td className="px-6 py-3.5 text-sm font-mono text-slate-400">{s.numCommande}</td>
              <td className="px-6 py-3.5"><StatusBadge status={s.statut as Status} /></td>
              <td className="px-6 py-3.5 text-right text-sm font-bold font-mono text-[#0F2056]">
                {s.statut === "Nette" ? `${s.prime} €` : <span className="text-slate-300">—</span>}
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  </div>
  );
};

// ─── NOUVELLE VENTE ───────────────────────────────────────────────────────────

const NouvelleVente = () => {
  const { addSale } = useSales();
  const [date, setDate]       = useState("2026-06-30");
  const [pointVente, setPointVente] = useState("");
  const [produit, setProduit] = useState<Product | "">("");
  const [offre, setOffre]     = useState("");
  const [numCommande, setNumCommande] = useState("");
  const [numBascule, setNumBascule]   = useState("");
  const [domaine, setDomaine] = useState("FAI");
  const [valeur, setValeur]   = useState("");
  const [porta, setPorta]     = useState(false);
  const [pto, setPto]         = useState(false);
  const [conv, setConv]       = useState(false);
  const [done, setDone]       = useState(false);
  const [saving, setSaving]   = useState(false);
  const [error, setError]     = useState<string | null>(null);

  const inputCls = "w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-[#1D4ED8] transition-all";
  const labelCls = "block text-sm font-semibold text-slate-700 mb-1.5";

  const reset = () => {
    setDone(false); setProduit(""); setOffre(""); setPorta(false); setPto(false); setConv(false);
    setNumCommande(""); setNumBascule(""); setPointVente(""); setValeur(""); setDomaine("FAI"); setError(null);
  };

  const submit = async () => {
    setError(null);
    if (!produit || !offre || !numCommande || !date || valeur === "") {
      setError("Merci de remplir les champs obligatoires (date, produit, offre, n° commande, valeur).");
      return;
    }
    setSaving(true);
    try {
      await addSale({
        date,
        type: produit,
        offre,
        numCommande,
        numBascule,
        domaine,
        porta,
        pto,
        convergence: conv,
        valeur: Number(valeur),
        pointVente,
      });
      setDone(true);
    } catch (e) {
      setError(e instanceof Error ? e.message : "Erreur lors de l'enregistrement");
    } finally {
      setSaving(false);
    }
  };

  if (done) {
    return (
      <div className="p-8 flex items-center justify-center" style={{ minHeight: 400 }}>
        <div className="text-center">
          <div className="w-16 h-16 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <Check size={30} className="text-emerald-600" />
          </div>
          <h2 className="text-xl font-bold text-[#0F2056] mb-2">Vente envoyée pour validation</h2>
          <p className="text-slate-500 text-sm mb-6">Elle apparaîtra dans votre liste sous le statut <strong>Brute</strong>.</p>
          <button
            onClick={reset}
            className="bg-[#1D4ED8] text-white px-6 py-2.5 rounded-xl text-sm font-semibold hover:bg-[#1E40AF] transition-colors"
          >
            Saisir une nouvelle vente
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="p-8">
      <div className="max-w-2xl">
        <div className="bg-white rounded-2xl border border-black/[0.06] p-8">
          <h2 className="text-sm font-bold text-[#0F2056] mb-6">Informations de la vente</h2>

          <div className="space-y-5">
            {/* Row 1 */}
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className={labelCls}>Date de la vente *</label>
                <input type="date" value={date} onChange={e => setDate(e.target.value)} className={inputCls} />
              </div>
              <div>
                <label className={labelCls}>Point de vente</label>
                <input type="text" value={pointVente} onChange={e => setPointVente(e.target.value)} placeholder="ex: Paris 11e" className={inputCls} />
              </div>
            </div>

            {/* Row 2 */}
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className={labelCls}>Type de produit *</label>
                <select
                  value={produit}
                  onChange={e => { setProduit(e.target.value as Product); setOffre(""); }}
                  className={inputCls + " cursor-pointer"}
                >
                  <option value="">Sélectionner...</option>
                  {PRODUCTS.map(p => <option key={p} value={p}>{p}</option>)}
                </select>
              </div>
              <div>
                <label className={labelCls}>Offre *</label>
                <select
                  value={offre}
                  onChange={e => setOffre(e.target.value)}
                  disabled={!produit}
                  className={inputCls + " cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed"}
                >
                  <option value="">Sélectionner...</option>
                  {produit && OFFERS[produit].map(o => <option key={o} value={o}>{o}</option>)}
                </select>
              </div>
            </div>

            {/* Row 3 */}
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className={labelCls}>N° de commande *</label>
                <input type="text" value={numCommande} onChange={e => setNumCommande(e.target.value)} placeholder="CMD-2026-XXXX" className={inputCls + " font-mono"} />
              </div>
              <div>
                <label className={labelCls}>N° bascule</label>
                <input type="text" value={numBascule} onChange={e => setNumBascule(e.target.value)} placeholder="BAS-XXXX" className={inputCls + " font-mono"} />
              </div>
            </div>

            {/* Row 4 */}
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className={labelCls}>Domaine *</label>
                <select value={domaine} onChange={e => setDomaine(e.target.value)} className={inputCls + " cursor-pointer"}>
                  <option>FAI</option>
                  <option>Mobile</option>
                </select>
              </div>
              <div>
                <label className={labelCls}>Valeur (€) *</label>
                <input type="number" value={valeur} onChange={e => setValeur(e.target.value)} placeholder="0.00" step="0.01" min="0" className={inputCls + " font-mono"} />
              </div>
            </div>

            {/* Toggles */}
            <div className="bg-slate-50 rounded-xl px-5 py-4 space-y-3.5 border border-slate-100">
              <p className="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">Options</p>
              <Toggle label="PORTA — Portabilité numéro" value={porta} onChange={setPorta} />
              <Toggle label="PTO — Présence technicien requis" value={pto} onChange={setPto} />
              <Toggle label="Convergence (Fixe + Mobile)" value={conv} onChange={setConv} />
            </div>

            {error && (
              <div className="bg-red-50 border border-red-200 text-red-600 text-sm rounded-xl px-4 py-3">{error}</div>
            )}

            <button
              onClick={submit}
              disabled={saving}
              className="w-full bg-[#1D4ED8] hover:bg-[#1E40AF] text-white py-3 rounded-xl font-semibold text-sm transition-colors shadow-md shadow-blue-100 disabled:opacity-50"
            >
              {saving ? "Envoi..." : "Envoyer pour validation"}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

// ─── MES VENTES ───────────────────────────────────────────────────────────────

const MesVentes = () => {
  const [filterStatus, setFilterStatus] = useState<"Tous" | Status>("Tous");
  const [search, setSearch] = useState("");

  const { sales } = useSales();

  const filtered = sales.filter(s => {
    if (filterStatus !== "Tous" && s.statut !== filterStatus) return false;
    if (search) {
      const q = search.toLowerCase();
      if (!s.numCommande.toLowerCase().includes(q) && !s.offre.toLowerCase().includes(q) && !s.type.toLowerCase().includes(q)) return false;
    }
    return true;
  });

  const filterBtn = (label: string, val: "Tous" | Status) => {
    const active = filterStatus === val;
    const colors: Record<string, string> = {
      Tous:    "bg-[#1D4ED8] text-white",
      Brute:   "bg-amber-500 text-white",
      Nette:   "bg-emerald-500 text-white",
      Rejetée: "bg-red-500 text-white",
    };
    return (
      <button
        key={val}
        onClick={() => setFilterStatus(val)}
        className={`px-3.5 py-2 rounded-xl text-sm font-semibold transition-all ${
          active ? colors[val] : "bg-white border border-slate-200 text-slate-500 hover:bg-slate-50"
        }`}
      >
        {label}
      </button>
    );
  };

  return (
    <div className="p-8">
      <div className="flex items-center gap-3 mb-6 flex-wrap">
        <div className="relative">
          <Search size={15} className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
          <input
            type="text"
            placeholder="Commande, offre, produit..."
            value={search}
            onChange={e => setSearch(e.target.value)}
            className="pl-9 pr-4 py-2.5 border border-slate-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-[#1D4ED8] w-56"
          />
        </div>
        <div className="flex gap-2">
          {filterBtn("Tous", "Tous")}
          {filterBtn("Brute", "Brute")}
          {filterBtn("Nette", "Nette")}
          {filterBtn("Rejetée", "Rejetée")}
        </div>
        <input
          type="month"
          defaultValue="2026-06"
          className="ml-auto px-4 py-2.5 border border-slate-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-200"
        />
      </div>

      <div className="bg-white rounded-2xl border border-black/[0.06] overflow-hidden">
        <table className="w-full">
          <thead>
            <tr className="text-[11px] text-slate-400 font-bold uppercase tracking-wider border-b border-slate-100">
              <th className="text-left px-6 py-4">Date</th>
              <th className="text-left px-6 py-4">Produit</th>
              <th className="text-left px-6 py-4">Offre</th>
              <th className="text-left px-6 py-4">N° Commande</th>
              <th className="text-left px-6 py-4">N° Bascule</th>
              <th className="text-left px-6 py-4">Domaine</th>
              <th className="text-left px-6 py-4">Statut</th>
              <th className="text-right px-6 py-4">Prime</th>
            </tr>
          </thead>
          <tbody>
            {filtered.map(s => (
              <tr key={s.id} className="border-t border-slate-50 hover:bg-slate-50/60 transition-colors group">
                <td className="px-6 py-3.5 text-sm font-mono text-slate-400">{s.date}</td>
                <td className="px-6 py-3.5">
                  <span
                    className="text-xs font-bold px-2 py-1 rounded-lg text-white"
                    style={{ backgroundColor: PRODUCT_COLORS[s.type as Product] }}
                  >
                    {s.type}
                  </span>
                </td>
                <td className="px-6 py-3.5 text-sm text-slate-700">{s.offre}</td>
                <td className="px-6 py-3.5 text-sm font-mono text-slate-400">{s.numCommande}</td>
                <td className="px-6 py-3.5 text-sm font-mono text-slate-400">{s.numBascule}</td>
                <td className="px-6 py-3.5 text-sm text-slate-500">{s.domaine}</td>
                <td className="px-6 py-3.5"><StatusBadge status={s.statut as Status} /></td>
                <td className="px-6 py-3.5 text-right text-sm font-bold font-mono text-[#0F2056]">
                  {s.statut === "Nette" ? `${s.prime} €` : <span className="text-slate-300">—</span>}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
        <div className="px-6 py-3.5 border-t border-slate-100 text-xs text-slate-400">
          {filtered.length} vente{filtered.length > 1 ? "s" : ""}
        </div>
      </div>
    </div>
  );
};

// ─── SUPERVISEUR DASHBOARD ────────────────────────────────────────────────────

const SupDashboard = () => {
  const { sales } = useSales();
  const pending = sales.filter(s => s.statut === "Brute").length;
  const sorted  = [...AGENT_PRIMES].sort((a, b) => {
    const ta = PRODUCTS.reduce((s, p) => s + (a[p] || 0), 0);
    const tb = PRODUCTS.reduce((s, p) => s + (b[p] || 0), 0);
    return tb - ta;
  });
  const maxTotal = PRODUCTS.reduce((s, p) => s + (sorted[0][p] || 0), 0);

  return (
    <div className="p-8 space-y-7">
      <div className="grid grid-cols-4 gap-5">
        <div className="bg-white rounded-2xl p-6 border-2 border-amber-200 relative overflow-hidden">
          <div className="absolute -right-3 -top-3 w-20 h-20 bg-amber-50 rounded-full" />
          <div className="relative">
            <div className="flex items-center gap-2 mb-3">
              <Clock size={16} className="text-amber-500" />
              <span className="text-xs font-bold text-amber-600 uppercase tracking-wider">En attente</span>
            </div>
            <div className="text-4xl font-bold text-amber-500 mb-1">{pending}</div>
            <div className="text-sm text-slate-500">ventes à valider</div>
          </div>
        </div>
        <StatCard icon={Check}  label="Ventes validées"    value="34"       sub="Juin 2026"   accent="bg-emerald-500" />
        <StatCard icon={Euro}   label="Primes distribuées" value="1 430 €"  sub="Ce mois"     accent="bg-[#1D4ED8]"  />
        <StatCard icon={Users}  label="Agents actifs"      value="5"        sub="Ce mois"     accent="bg-violet-500" />
      </div>

      <div className="grid grid-cols-5 gap-6">
        {/* Agent ranking */}
        <div className="col-span-3 bg-white rounded-2xl border border-black/[0.06] p-6">
          <h3 className="font-bold text-[#0F2056] text-sm mb-1">Primes validées par agent</h3>
          <p className="text-xs text-slate-400 mb-5">Classement — Juin 2026</p>
          <div className="space-y-4">
            {sorted.map((row, i) => {
              const total = PRODUCTS.reduce((s, p) => s + (row[p] || 0), 0);
              return (
                <div key={row.agent} className="flex items-center gap-3">
                  <span className={`w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0 ${
                    i === 0 ? "bg-amber-100 text-amber-700" : "bg-slate-100 text-slate-500"
                  }`}>{i + 1}</span>
                  <div className="flex-1">
                    <div className="flex justify-between text-sm mb-1.5">
                      <span className="font-semibold text-slate-800">{row.agent}</span>
                      <span className="font-bold font-mono text-[#1D4ED8]">{total} €</span>
                    </div>
                    <div className="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                      <div
                        className="h-full bg-[#1D4ED8] rounded-full transition-all"
                        style={{ width: `${(total / maxTotal) * 100}%` }}
                      />
                    </div>
                  </div>
                </div>
              );
            })}
          </div>
        </div>

        {/* Pie */}
        <div className="col-span-2 bg-white rounded-2xl border border-black/[0.06] p-6">
          <h3 className="font-bold text-[#0F2056] text-sm mb-1">Ventes par produit</h3>
          <p className="text-xs text-slate-400 mb-3">Part relative — mois en cours</p>
          <ResponsiveContainer width="100%" height={150}>
            <PieChart>
              <Pie
                data={PRODUCT_DIST}
                cx="50%" cy="50%"
                innerRadius={42} outerRadius={68}
                paddingAngle={3}
                dataKey="value"
              >
                {PRODUCT_DIST.map((entry, i) => (
                  <Cell key={i} fill={PRODUCT_COLORS[entry.name as Product]} />
                ))}
              </Pie>
              <Tooltip formatter={(v: any) => [`${v}%`, ""]} contentStyle={{ borderRadius: 10, border: "none", fontSize: 12 }} />
            </PieChart>
          </ResponsiveContainer>
          <div className="space-y-1.5 mt-1">
            {PRODUCT_DIST.map(p => (
              <div key={p.name} className="flex items-center justify-between text-xs">
                <div className="flex items-center gap-2">
                  <span className="w-2.5 h-2.5 rounded-full" style={{ backgroundColor: PRODUCT_COLORS[p.name as Product] }} />
                  <span className="text-slate-600 font-medium">{p.name}</span>
                </div>
                <span className="font-bold text-slate-700 font-mono">{p.value}%</span>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
};

// ─── FILE DE VALIDATION ───────────────────────────────────────────────────────

const FileValidation = () => {
  const { sales: allSales, validateSale, rejectSale: rejectSaleApi } = useSales();
  const sales = allSales.filter(s => s.statut === "Brute");
  const [expanded, setExpanded] = useState<number | null>(null);
  const [rejectId, setRejectId] = useState<number | null>(null);
  const [motif, setMotif]       = useState("");
  const [busy, setBusy]         = useState(false);

  const validate = async (id: number) => {
    setBusy(true);
    try { await validateSale(id); } finally { setBusy(false); }
  };
  const confirmReject = async () => {
    if (rejectId == null) return;
    setBusy(true);
    try {
      await rejectSaleApi(rejectId, motif);
      setRejectId(null);
      setMotif("");
    } finally { setBusy(false); }
  };

  const rejectSale = rejectId != null ? allSales.find(s => s.id === rejectId) : null;

  return (
    <div className="p-8">
      <div className="flex items-center gap-3 mb-6">
        <h2 className="text-sm font-bold text-[#0F2056]">Ventes à traiter</h2>
        {sales.length > 0 && (
          <span className="bg-amber-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">{sales.length}</span>
        )}
      </div>

      <div className="space-y-3">
        {sales.length === 0 ? (
          <div className="bg-white rounded-2xl border border-black/[0.06] p-16 text-center">
            <div className="w-14 h-14 bg-emerald-50 rounded-full flex items-center justify-center mx-auto mb-4">
              <Check size={26} className="text-emerald-500" />
            </div>
            <p className="font-semibold text-slate-600">File vide — toutes les ventes ont été traitées</p>
            <p className="text-sm text-slate-400 mt-1">Revenez plus tard pour de nouvelles entrées.</p>
          </div>
        ) : (
          sales.map(sale => (
            <div
              key={sale.id}
              className={`bg-white rounded-2xl border transition-all ${
                expanded === sale.id ? "border-[#1D4ED8]/40 shadow-md" : "border-black/[0.06] hover:border-slate-200 hover:shadow-sm"
              }`}
            >
              {/* Row */}
              <div
                className="flex items-center gap-4 px-6 py-4 cursor-pointer"
                onClick={() => setExpanded(expanded === sale.id ? null : sale.id)}
              >
                <div className="flex-1 min-w-0">
                  <div className="flex items-center gap-2.5 mb-1">
                    <span
                      className="text-xs font-bold px-2 py-0.5 rounded-lg text-white"
                      style={{ backgroundColor: PRODUCT_COLORS[sale.type as Product] }}
                    >
                      {sale.type}
                    </span>
                    <span className="text-sm font-semibold text-[#0F2056]">{sale.offre}</span>
                    <StatusBadge status="Brute" />
                  </div>
                  <div className="flex items-center gap-3 text-xs text-slate-400 font-mono">
                    <span>{sale.numCommande}</span>
                    <span className="text-slate-200">|</span>
                    <span className="not-italic font-sans font-medium text-slate-500">{sale.agent}</span>
                    <span className="text-slate-200">|</span>
                    <span>{sale.date}</span>
                  </div>
                </div>
                <div className="flex items-center gap-2 flex-shrink-0">
                  <button
                    onClick={e => { e.stopPropagation(); validate(sale.id); }}
                    className="flex items-center gap-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 px-3.5 py-2 rounded-xl text-sm font-semibold transition-colors"
                  >
                    <Check size={14} /> Valider
                  </button>
                  <button
                    onClick={e => { e.stopPropagation(); setRejectId(sale.id); }}
                    className="flex items-center gap-1.5 bg-red-50 hover:bg-red-100 text-red-600 px-3.5 py-2 rounded-xl text-sm font-semibold transition-colors"
                  >
                    <X size={14} /> Rejeter
                  </button>
                  <Eye size={15} className={`ml-1 transition-colors ${expanded === sale.id ? "text-[#1D4ED8]" : "text-slate-300"}`} />
                </div>
              </div>

              {/* Detail expand */}
              {expanded === sale.id && (
                <div className="px-6 pb-5 border-t border-slate-100 pt-4">
                  <div className="grid grid-cols-3 gap-x-6 gap-y-4">
                    {[
                      ["Produit",       sale.type],
                      ["Offre",         sale.offre],
                      ["N° commande",   sale.numCommande],
                      ["N° bascule",    sale.numBascule],
                      ["Domaine",       sale.domaine],
                      ["Valeur",        `${sale.valeur} €`],
                      ["Point de vente",sale.pointVente || "—"],
                      ["PORTA",         sale.porta ? "OUI" : "NON"],
                      ["PTO",           sale.pto ? "OUI" : "NON"],
                      ["Convergence",   sale.convergence ? "OUI" : "NON"],
                    ].map(([label, val]) => (
                      <div key={label}>
                        <div className="text-[11px] text-slate-400 font-semibold uppercase tracking-wider mb-0.5">{label}</div>
                        <div className="text-sm font-semibold text-slate-800">{val}</div>
                      </div>
                    ))}
                  </div>
                </div>
              )}
            </div>
          ))
        )}
      </div>

      {/* Reject modal */}
      {rejectId !== null && (
        <div
          className="fixed inset-0 bg-black/25 backdrop-blur-sm flex items-center justify-center z-50"
          onClick={() => setRejectId(null)}
        >
          <div
            className="bg-white rounded-2xl p-8 w-full max-w-md shadow-2xl"
            onClick={e => e.stopPropagation()}
          >
            <div className="flex items-start justify-between mb-2">
              <div className="w-10 h-10 bg-red-50 rounded-xl flex items-center justify-center mb-4">
                <AlertCircle size={20} className="text-red-500" />
              </div>
              <button onClick={() => setRejectId(null)} className="text-slate-400 hover:text-slate-600 transition-colors">
                <X size={20} />
              </button>
            </div>
            <h3 className="font-bold text-[#0F2056] mb-1">Motif de rejet</h3>
            <p className="text-sm text-slate-500 mb-4">
              Vente <span className="font-mono font-semibold text-slate-700">{rejectSale?.numCommande}</span> — {rejectSale?.offre}
            </p>
            <textarea
              value={motif}
              onChange={e => setMotif(e.target.value)}
              placeholder="Ex: Numéro de commande invalide, doublon détecté, offre non éligible..."
              rows={3}
              className="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-red-200 focus:border-red-400 resize-none bg-slate-50"
            />
            <div className="flex gap-3 mt-5">
              <button
                onClick={() => setRejectId(null)}
                className="flex-1 py-2.5 border border-slate-200 rounded-xl text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-colors"
              >
                Annuler
              </button>
              <button
                onClick={confirmReject}
                disabled={!motif.trim()}
                className="flex-1 py-2.5 bg-red-500 hover:bg-red-600 text-white rounded-xl text-sm font-semibold transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
              >
                Confirmer le rejet
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

// ─── TABLEAU DES PRIMES ───────────────────────────────────────────────────────

const TableauPrimes = () => {
  const totals = PRODUCTS.reduce((acc, p) => {
    acc[p] = AGENT_PRIMES.reduce((s, row) => s + (row[p] || 0), 0);
    return acc;
  }, {} as Record<Product, number>);

  const grandTotal = Object.values(totals).reduce((a, b) => a + b, 0);

  return (
    <div className="p-8">
      <div className="flex items-center justify-between mb-6">
        <div className="flex items-center gap-3">
          <input
            type="month"
            defaultValue="2026-06"
            className="px-4 py-2.5 border border-slate-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-200"
          />
        </div>
        <div className="flex gap-3">
          <button className="flex items-center gap-2 px-4 py-2.5 border border-slate-200 bg-white rounded-xl text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-colors">
            <Download size={15} />
            Exporter Excel
          </button>
          <button className="flex items-center gap-2 px-4 py-2.5 bg-[#1D4ED8] hover:bg-[#1E40AF] rounded-xl text-sm font-semibold text-white transition-colors">
            <Download size={15} />
            Exporter PDF
          </button>
        </div>
      </div>

      <div className="bg-white rounded-2xl border border-black/[0.06] overflow-hidden">
        <table className="w-full">
          <thead>
            <tr className="bg-[#0F2056] text-white text-sm">
              <th className="text-left px-6 py-4 font-semibold rounded-tl-2xl">Agent</th>
              {PRODUCTS.map(p => (
                <th key={p} className="text-center px-5 py-4 font-semibold">{p}</th>
              ))}
              <th className="text-right px-6 py-4 font-semibold rounded-tr-2xl">Total</th>
            </tr>
          </thead>
          <tbody>
            {AGENT_PRIMES.map((row, i) => {
              const total = PRODUCTS.reduce((s, p) => s + (row[p] || 0), 0);
              return (
                <tr
                  key={row.agent}
                  className={`border-t border-slate-50 hover:bg-blue-50/30 transition-colors ${i % 2 === 0 ? "" : "bg-slate-50/40"}`}
                >
                  <td className="px-6 py-4 text-sm font-bold text-[#0F2056]">{row.agent}</td>
                  {PRODUCTS.map(p => (
                    <td key={p} className="px-5 py-4 text-center text-sm font-mono text-slate-700">
                      {row[p] ? (
                        <span className="font-semibold text-[#0F2056]">{row[p]} €</span>
                      ) : (
                        <span className="text-slate-300">—</span>
                      )}
                    </td>
                  ))}
                  <td className="px-6 py-4 text-right text-sm font-bold font-mono text-[#1D4ED8]">{total} €</td>
                </tr>
              );
            })}
          </tbody>
          <tfoot>
            <tr className="border-t-2 border-slate-200 bg-slate-50">
              <td className="px-6 py-4 text-sm font-bold text-[#0F2056]">Total</td>
              {PRODUCTS.map(p => (
                <td key={p} className="px-5 py-4 text-center text-sm font-bold font-mono text-[#0F2056]">{totals[p]} €</td>
              ))}
              <td className="px-6 py-4 text-right text-sm font-bold font-mono text-[#1D4ED8] text-base">{grandTotal} €</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  );
};

// ─── GESTION BARÈMES ──────────────────────────────────────────────────────────

const GestionBaremes = () => {
  const [showForm, setShowForm] = useState(false);
  const [newRow, setNewRow]     = useState({ produit: "", offre: "", prime: "", dateEffet: "2026-07-01" });

  const inputCls = "px-4 py-2.5 border border-slate-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-[#1D4ED8] transition-all";

  return (
    <div className="p-8">
      <div className="flex items-center justify-between mb-6">
        <p className="text-sm text-slate-500">
          Les anciens barèmes sont conservés à titre historique. Chaque nouveau tarif est horodaté.
        </p>
        <button
          onClick={() => setShowForm(!showForm)}
          className="flex items-center gap-2 px-4 py-2.5 bg-[#1D4ED8] hover:bg-[#1E40AF] rounded-xl text-sm font-semibold text-white transition-colors"
        >
          <Plus size={15} />
          Nouveau tarif
        </button>
      </div>

      {showForm && (
        <div className="bg-blue-50/70 border border-blue-200/70 rounded-2xl p-6 mb-6">
          <h3 className="text-sm font-bold text-[#0F2056] mb-4">Nouveau barème</h3>
          <div className="grid grid-cols-4 gap-4">
            <select
              value={newRow.produit}
              onChange={e => setNewRow(r => ({ ...r, produit: e.target.value, offre: "" }))}
              className={inputCls + " cursor-pointer"}
            >
              <option value="">Produit...</option>
              {PRODUCTS.map(p => <option key={p}>{p}</option>)}
            </select>
            <select
              value={newRow.offre}
              onChange={e => setNewRow(r => ({ ...r, offre: e.target.value }))}
              disabled={!newRow.produit}
              className={inputCls + " cursor-pointer disabled:opacity-40"}
            >
              <option value="">Offre...</option>
              {newRow.produit && OFFERS[newRow.produit as Product]?.map(o => <option key={o}>{o}</option>)}
            </select>
            <input
              type="number"
              placeholder="Prime (€)"
              value={newRow.prime}
              onChange={e => setNewRow(r => ({ ...r, prime: e.target.value }))}
              className={inputCls + " font-mono"}
            />
            <input
              type="date"
              value={newRow.dateEffet}
              onChange={e => setNewRow(r => ({ ...r, dateEffet: e.target.value }))}
              className={inputCls}
            />
          </div>
          <div className="flex justify-end gap-3 mt-4">
            <button onClick={() => setShowForm(false)} className="px-4 py-2 text-sm text-slate-500 hover:text-slate-700 transition-colors">
              Annuler
            </button>
            <button className="px-5 py-2.5 bg-[#1D4ED8] text-white rounded-xl text-sm font-semibold hover:bg-[#1E40AF] transition-colors">
              Enregistrer
            </button>
          </div>
        </div>
      )}

      <div className="bg-white rounded-2xl border border-black/[0.06] overflow-hidden">
        <table className="w-full">
          <thead>
            <tr className="text-[11px] text-slate-400 font-bold uppercase tracking-wider border-b border-slate-100">
              <th className="text-left px-6 py-4">Produit</th>
              <th className="text-left px-6 py-4">Offre</th>
              <th className="text-right px-6 py-4">Prime (€)</th>
              <th className="text-left px-6 py-4">Date d'effet</th>
              <th className="text-left px-6 py-4">Statut</th>
              <th className="text-right px-6 py-4">Actions</th>
            </tr>
          </thead>
          <tbody>
            {BAREMES.map(b => (
              <tr
                key={b.id}
                className={`border-t border-slate-50 transition-colors ${
                  !b.actif ? "opacity-45" : "hover:bg-slate-50/60"
                }`}
              >
                <td className="px-6 py-3.5">
                  <span
                    className="text-xs font-bold px-2 py-0.5 rounded-lg text-white"
                    style={{ backgroundColor: PRODUCT_COLORS[b.produit as Product] }}
                  >
                    {b.produit}
                  </span>
                </td>
                <td className="px-6 py-3.5 text-sm text-slate-700 font-medium">{b.offre}</td>
                <td className="px-6 py-3.5 text-right text-sm font-bold font-mono text-[#1D4ED8]">{b.prime} €</td>
                <td className="px-6 py-3.5 text-sm font-mono text-slate-400">{b.dateEffet}</td>
                <td className="px-6 py-3.5">
                  {b.actif ? (
                    <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
                      <span className="w-1.5 h-1.5 rounded-full bg-emerald-500" />
                      Actif
                    </span>
                  ) : (
                    <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-400">
                      <span className="w-1.5 h-1.5 rounded-full bg-slate-300" />
                      Archivé
                    </span>
                  )}
                </td>
                <td className="px-6 py-3.5 text-right">
                  {b.actif && (
                    <button className="p-1.5 text-slate-300 hover:text-[#1D4ED8] hover:bg-blue-50 rounded-lg transition-colors">
                      <Edit2 size={14} />
                    </button>
                  )}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
};

// ─── ROOT ─────────────────────────────────────────────────────────────────────

export default function App() {
  const [view, setView] = useState<View>("login");
  const [role, setRole] = useState<Role>("agent");

  const handleLogin = (r: Role) => {
    setRole(r);
    setView(r === "agent" ? "agent-dashboard" : "sup-dashboard");
  };

  if (view === "login") return <LoginScreen onLogin={handleLogin} />;

  const META: Record<View, { title: string; sub: string }> = {
    login:                { title: "",                    sub: "" },
    "agent-dashboard":    { title: "Tableau de bord",     sub: "Bienvenue Sophie Martin — Juin 2026" },
    "agent-nouvelle-vente":{ title: "Nouvelle vente",     sub: "Saisir une vente pour validation superviseur" },
    "agent-mes-ventes":   { title: "Mes ventes",          sub: "Historique complet de vos ventes saisies" },
    "sup-dashboard":      { title: "Tableau de bord",     sub: "Vue d'ensemble équipe — Juin 2026" },
    "sup-validation":     { title: "File de validation",  sub: "Ventes en attente de traitement" },
    "sup-primes":         { title: "Tableau des primes",  sub: "Récapitulatif mensuel par agent et produit" },
    "sup-baremes":        { title: "Gestion des barèmes", sub: "Tarification des primes — historique et édition" },
  };

  const renderView = () => {
    switch (view) {
      case "agent-dashboard":     return <AgentDashboard />;
      case "agent-nouvelle-vente":return <NouvelleVente />;
      case "agent-mes-ventes":    return <MesVentes />;
      case "sup-dashboard":       return <SupDashboard />;
      case "sup-validation":      return <FileValidation />;
      case "sup-primes":          return <TableauPrimes />;
      case "sup-baremes":         return <GestionBaremes />;
      default:                    return null;
    }
  };

  const { title, sub } = META[view];

  return (
    <SalesProvider>
      <div className="min-h-screen bg-[#F1F5F9]" style={{ fontFamily: "'Inter', sans-serif" }}>
        <Sidebar role={role} view={view} onNavigate={setView} onLogout={() => setView("login")} />
        <div className="ml-60">
          <Header title={title} subtitle={sub} />
          <main className="pt-[60px] min-h-screen">
            {renderView()}
          </main>
        </div>
      </div>
    </SalesProvider>
  );
}
