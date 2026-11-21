/**
 * Minimal typed client for the FleetCore API.
 * Server components use API_INTERNAL_URL (container network); the browser uses NEXT_PUBLIC_API_URL (same origin via Caddy).
 */

export type HealthStatus = "ok" | "degraded";

export interface HealthResponse {
  status: HealthStatus;
  service: string;
  env: string;
  checks: Record<string, string>;
  time: string;
}

export function apiBaseUrl(): string {
  if (typeof window === "undefined") {
    return process.env.API_INTERNAL_URL ?? "http://localhost:8080/api";
  }
  return process.env.NEXT_PUBLIC_API_URL ?? "/api";
}

export async function fetchHealth(fetchImpl: typeof fetch = fetch): Promise<HealthResponse> {
  const response = await fetchImpl(`${apiBaseUrl()}/health`, { cache: "no-store" });
  if (!response.ok && response.status !== 503) {
    throw new Error(`API health request failed with status ${response.status}`);
  }
  return (await response.json()) as HealthResponse;
}
