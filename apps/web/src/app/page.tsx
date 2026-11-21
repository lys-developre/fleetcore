import { fetchHealth, type HealthResponse } from "@/shared/api/client";

export const dynamic = "force-dynamic";

async function loadHealth(): Promise<HealthResponse | null> {
  try {
    return await fetchHealth();
  } catch {
    return null;
  }
}

export default async function HomePage() {
  const health = await loadHealth();
  const apiUp = health?.status === "ok";

  return (
    <main className="mx-auto flex min-h-screen max-w-xl flex-col justify-center gap-8 px-6 py-16">
      <header className="space-y-2">
        <p className="text-sm font-medium uppercase tracking-widest text-zinc-500">FleetCore</p>
        <h1 className="text-3xl font-semibold tracking-tight">Albaranes digitales para maquinaria y obra</h1>
        <p className="text-zinc-600 dark:text-zinc-400">
          Oficina prepara los datos, el operario rellena el albarán desde el móvil y el encargado firma. Hito H0: cimientos.
        </p>
      </header>

      <section aria-labelledby="status-heading" className="rounded-xl border border-zinc-200 p-5 dark:border-zinc-800">
        <h2 id="status-heading" className="mb-3 text-sm font-semibold text-zinc-500">
          Estado de los servicios
        </h2>
        <dl className="grid grid-cols-[auto_1fr] gap-x-6 gap-y-2 text-sm">
          <dt className="text-zinc-500">Web</dt>
          <dd className="font-medium text-emerald-600">En marcha</dd>
          <dt className="text-zinc-500">API</dt>
          <dd className={apiUp ? "font-medium text-emerald-600" : "font-medium text-red-600"}>
            {health ? (apiUp ? "En marcha" : "Degradada") : "Sin respuesta"}
          </dd>
          <dt className="text-zinc-500">Base de datos</dt>
          <dd className={health?.checks.database === "ok" ? "font-medium text-emerald-600" : "font-medium text-red-600"}>
            {health?.checks.database === "ok" ? "Conectada" : "No disponible"}
          </dd>
          {health ? (
            <>
              <dt className="text-zinc-500">Entorno</dt>
              <dd className="font-mono">{health.env}</dd>
            </>
          ) : null}
        </dl>
      </section>
    </main>
  );
}
