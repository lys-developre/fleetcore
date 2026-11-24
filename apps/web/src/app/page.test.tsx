import { render, screen } from "@testing-library/react";
import { beforeEach, describe, expect, it, vi } from "vitest";
import type { HealthResponse } from "@/shared/api/client";

const fetchHealth = vi.fn<() => Promise<HealthResponse>>();
vi.mock("@/shared/api/client", () => ({ fetchHealth: () => fetchHealth() }));

const { default: HomePage } = await import("./page");

function health(overrides: Partial<HealthResponse> = {}): HealthResponse {
  return { status: "ok", service: "fleetcore-api", env: "dev", checks: { database: "ok" }, time: "2026-11-23T12:00:00+01:00", ...overrides };
}

async function renderPage() {
  render(await HomePage());
}

function statusOf(label: string): HTMLElement {
  const term = screen.getByText(label, { selector: "dt" });
  return term.nextElementSibling as HTMLElement;
}

describe("HomePage service status", () => {
  beforeEach(() => {
    fetchHealth.mockReset();
  });

  it("shows API and database up when the API reports ok", async () => {
    fetchHealth.mockResolvedValue(health());

    await renderPage();

    expect(statusOf("API")).toHaveTextContent("En marcha");
    expect(statusOf("Base de datos")).toHaveTextContent("Conectada");
    expect(statusOf("Entorno")).toHaveTextContent("dev");
  });

  it("shows a degraded API and the database down when the API reports degraded", async () => {
    fetchHealth.mockResolvedValue(health({ status: "degraded", checks: { database: "unavailable" } }));

    await renderPage();

    expect(statusOf("API")).toHaveTextContent("Degradada");
    expect(statusOf("Base de datos")).toHaveTextContent("No disponible");
  });

  it("shows the API as unreachable and hides the environment when the request fails", async () => {
    fetchHealth.mockImplementation(async () => {
      throw new Error("ECONNREFUSED");
    });

    await renderPage();

    expect(statusOf("API")).toHaveTextContent("Sin respuesta");
    expect(statusOf("Base de datos")).toHaveTextContent("No disponible");
    expect(screen.queryByText("Entorno")).not.toBeInTheDocument();
  });
});
