// ─── API client (Symfony backend) ─────────────────────────────────────────────
// The Vite dev server proxies "/api" to the Symfony backend (see vite.config.ts).

const BASE = "/api";

export interface ApiUser {
  id: number;
  email: string;
  roles: string[];
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

// ─── Users ────────────────────────────────────────────────────────────────────

export const listUsers = () => http<ApiUser[]>("/users");

export const createUser = (payload: { email: string; password: string; roles?: string[] }) =>
  http<ApiUser>("/users", { method: "POST", body: JSON.stringify(payload) });

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
