import { defineConfig, devices } from "@playwright/test";

/**
 * End-to-end tests against a running stack, never against mocks.
 * Local dev:   ./dev.sh e2e                      (http://caddy inside the compose network)
 * CI smoke:    E2E_BASE_URL=https://localhost    (production images behind Caddy's internal CA)
 */
export default defineConfig({
  testDir: "./e2e",
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 1 : 0,
  reporter: process.env.CI ? [["list"], ["html", { open: "never" }]] : "list",
  use: {
    baseURL: process.env.E2E_BASE_URL ?? "http://localhost:8080",
    ignoreHTTPSErrors: true,
    trace: "retain-on-failure",
    locale: "es-ES",
  },
  projects: [
    { name: "desktop", use: { ...devices["Desktop Chrome"] } },
    { name: "mobile", use: { ...devices["Pixel 7"] } },
  ],
});
