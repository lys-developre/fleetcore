import { afterEach, describe, expect, it, vi } from "vitest";
import { apiBaseUrl, fetchHealth } from "./client";

describe("fetchHealth", () => {
  it("returns the parsed payload when the API answers 200", async () => {
    const fetchMock = vi.fn().mockResolvedValue(
      new Response(JSON.stringify({ status: "ok", service: "fleetcore-api", env: "test", checks: { database: "ok" }, time: "t" }), {
        status: 200,
        headers: { "content-type": "application/json" },
      }),
    );

    const health = await fetchHealth(fetchMock as unknown as typeof fetch);

    expect(health.status).toBe("ok");
    expect(health.checks.database).toBe("ok");
  });

  it("still parses a 503 degraded answer", async () => {
    const fetchMock = vi.fn().mockResolvedValue(
      new Response(JSON.stringify({ status: "degraded", service: "fleetcore-api", env: "test", checks: { database: "unavailable" }, time: "t" }), {
        status: 503,
      }),
    );

    const health = await fetchHealth(fetchMock as unknown as typeof fetch);

    expect(health.status).toBe("degraded");
  });

  it("throws on unexpected statuses", async () => {
    const fetchMock = vi.fn().mockResolvedValue(new Response("nope", { status: 500 }));

    await expect(fetchHealth(fetchMock as unknown as typeof fetch)).rejects.toThrow(/500/);
  });
});

describe("apiBaseUrl", () => {
  afterEach(() => {
    vi.unstubAllEnvs();
    vi.unstubAllGlobals();
  });

  it("uses the internal container URL on the server", () => {
    vi.stubGlobal("window", undefined);
    vi.stubEnv("API_INTERNAL_URL", "http://caddy:8081/api");

    expect(apiBaseUrl()).toBe("http://caddy:8081/api");
  });

  it("uses the public same-origin URL in the browser", () => {
    vi.stubEnv("API_INTERNAL_URL", "http://caddy:8081/api");
    vi.stubEnv("NEXT_PUBLIC_API_URL", "/api");

    expect(apiBaseUrl()).toBe("/api");
  });

  it("falls back to same-origin /api in the browser when nothing is configured", () => {
    vi.stubEnv("NEXT_PUBLIC_API_URL", undefined);

    expect(apiBaseUrl()).toBe("/api");
  });

  it("requests /health without caching", async () => {
    vi.stubEnv("NEXT_PUBLIC_API_URL", "/api");
    const fetchMock = vi.fn().mockResolvedValue(new Response("{}", { status: 200 }));

    await fetchHealth(fetchMock as unknown as typeof fetch);

    expect(fetchMock).toHaveBeenCalledWith("/api/health", { cache: "no-store" });
  });
});
