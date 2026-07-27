// ─── API client (Symfony backend) ─────────────────────────────────────────────
// The Vite dev server proxies "/api" to the Symfony backend (see vite.config.ts).

const BASE = "/api";

export interface ApiUser {
  id: number;
  email: string;
  roles: string[];
  manager?: { id: number; email: string } | null;
}

export interface ApiSale {
  id: number;
  agent: { id: number; email: string } | null;
  date: string;
  nCommande: string;
  bascule: string | null;
  domaine: string;
  type: string;
  offre: string;
  porta: boolean;
  pto: boolean;
  convergence: boolean;
  pointDeVente: string | null;
  valeur: string;
  statut: string;
  motifRejet: string | null;
  valideParId: { id: number; email: string } | null;
  dateValidation: string | null;
  createdAt: string | null;
  prime: string | null;
}

async function http<T>(url: string, init?: RequestInit): Promise<T> {
  const res = await fetch(BASE + url, {
    headers: { "Content-Type": "application/json" },
    ...init,
  });
  if (res.status === 204) return undefined as T;
  const body = await res.json().catch(() => null);
  if (!res.ok) {
    const msg =
      (Array.isArray(body?.errors) && body.errors.join(", ")) ||
      body?.error ||
      `HTTP ${res.status}`;
    throw new Error(msg);
  }
  return body as T;
}

// ─── Sales ────────────────────────────────────────────────────────────────────

export interface CreateSalePayload {
  agent: number;
  date: string;
  nCommande: string;
  bascule: string | null;
  domaine: string;
  type: string;
  offre: string;
  porta: boolean;
  pto: boolean;
  convergence: boolean;
  pointDeVente: string | null;
  valeur: number;
  statut: string;
}

export const listSales = () => http<ApiSale[]>("/sales");

export const createSale = (payload: CreateSalePayload) =>
  http<ApiSale>("/sales", { method: "POST", body: JSON.stringify(payload) });

export const updateSale = (id: number, payload: Record<string, unknown>) =>
  http<ApiSale>(`/sales/${id}`, { method: "PATCH", body: JSON.stringify(payload) });

export const deleteSale = (id: number) =>
  http<void>(`/sales/${id}`, { method: "DELETE" });

// ─── Auth ─────────────────────────────────────────────────────────────────────

export const login = (email: string, password: string) =>
  http<ApiUser>("/login", { method: "POST", body: JSON.stringify({ email, password }) });

export const logout = () => http<void>("/logout", { method: "POST" });

export const me = () => http<ApiUser>("/me");

// ─── Users ────────────────────────────────────────────────────────────────────

export const listUsers = () => http<ApiUser[]>("/users");

export const createUser = (payload: { email: string; password: string; roles?: string[] }) =>
  http<ApiUser>("/users", { method: "POST", body: JSON.stringify(payload) });

export const updateUser = (id: number, payload: { roles?: string[]; manager?: number | null }) =>
  http<ApiUser>(`/users/${id}`, { method: "PATCH", body: JSON.stringify(payload) });

// ─── Barèmes ─────────────────────────────────────────────────────

export interface ApiBareme {
  id: number;
  produit: string;
  offre: string;
  prime: string;
  dateEffet: string;
  dateFin: string | null;
  actif: boolean;
}

export interface BaremePayload {
  produit: string;
  offre: string;
  prime: number;
  dateEffet: string;
  dateFin?: string | null;
  actif?: boolean;
}

export const listBaremes = () => http<ApiBareme[]>("/baremes");

export const createBareme = (payload: BaremePayload) =>
  http<ApiBareme>("/baremes", { method: "POST", body: JSON.stringify(payload) });

export const updateBareme = (id: number, payload: Partial<BaremePayload>) =>
  http<ApiBareme>(`/baremes/${id}`, { method: "PATCH", body: JSON.stringify(payload) });

export const deleteBareme = (id: number) =>
  http<void>(`/baremes/${id}`, { method: "DELETE" });

// ─── Heures travaillées ─────────────────────────────────────────

export interface ApiWorkHours {
  id: number;
  agent: { id: number; email: string } | null;
  periode: string;
  heures: string;
  saisiPar: { id: number; email: string } | null;
  updatedAt: string | null;
}

export const listWorkHours = (periode?: string) =>
  http<ApiWorkHours[]>(`/work-hours${periode ? `?periode=${encodeURIComponent(periode)}` : ""}`);

export const saveWorkHours = (payload: { agent: number; periode: string; heures: number }) =>
  http<ApiWorkHours>("/work-hours", { method: "POST", body: JSON.stringify(payload) });

// ─── Résultats ─────────────────────────────────────────────

export interface ApiResult {
  agent: { id: number; email: string };
  periode: string;
  ca: number;
  primes: number;
  ventes: number;
  heures: number;
  caParHeure: number | null;
}

export const listResults = (periode?: string) =>
  http<ApiResult[]>(`/results${periode ? `?periode=${encodeURIComponent(periode)}` : ""}`);

// Derive a human display name from an email local part (e.g. sophie.martin -> Sophie Martin).
export const displayName = (email: string | null | undefined): string => {
  if (!email) return "—";
  const local = email.split("@")[0];
  return local
    .split(/[._-]+/)
    .filter(Boolean)
    .map((p) => p.charAt(0).toUpperCase() + p.slice(1))
    .join(" ");
};
// ─── Mon équipe ───────────────────────────────────────────────────────────────

export interface ApiAgent {
  id: number;
  nom: string | null;
  email: string;
  logAdmcc: string | null;
  roles: string[];
  manager?: { id: number; email: string } | null;
}

export interface CreateAgentPayload {
  nom: string;
  email: string;
  logAdmcc?: string;
  password: string;
}

export const listMyTeam = () =>
  http<ApiAgent[]>("/users/my-team");

export const createAgent = (payload: CreateAgentPayload) =>
  http<ApiAgent>("/users", { method: "POST", body: JSON.stringify(payload) });

export const deleteAgent = (id: number) =>
  http<void>(`/users/${id}`, { method: "DELETE" });