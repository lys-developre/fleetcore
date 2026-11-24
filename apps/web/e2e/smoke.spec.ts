import { expect, test } from "@playwright/test";

// Smoke: the whole chain answers. Browser -> Caddy -> Next.js -> (server side) Caddy -> PHP-FPM -> PostgreSQL.

test("the status page shows every service up", async ({ page }) => {
  await page.goto("/");

  await expect(page.getByRole("heading", { level: 1 })).toHaveText("Albaranes digitales para maquinaria y obra");
  const status = page.getByRole("region", { name: "Estado de los servicios" });
  await expect(status.getByRole("definition").nth(0)).toHaveText("En marcha");
  await expect(status.getByRole("definition").nth(1)).toHaveText("En marcha");
  await expect(status.getByRole("definition").nth(2)).toHaveText("Conectada");
});

test("the API health endpoint is reachable from the browser origin", async ({ request }) => {
  const response = await request.get("/api/health");

  expect(response.status()).toBe(200);
  expect(await response.json()).toMatchObject({ status: "ok", service: "fleetcore-api", checks: { database: "ok" } });
});

test("production responses carry the security headers", async ({ request, baseURL }) => {
  test.skip(!baseURL?.startsWith("https://"), "Only the production Caddyfile sets them");

  const response = await request.get("/");
  const headers = response.headers();

  expect(headers["strict-transport-security"]).toContain("max-age=31536000");
  expect(headers["x-content-type-options"]).toBe("nosniff");
  expect(headers["x-frame-options"]).toBe("DENY");
  expect(headers["server"]).toBeUndefined();
});
