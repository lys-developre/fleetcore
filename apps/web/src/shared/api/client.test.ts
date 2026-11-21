import { describe, expect, it, vi } from "vitest";
import { fetchHealth } from "./client";

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
