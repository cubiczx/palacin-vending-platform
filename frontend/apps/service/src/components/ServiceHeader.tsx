export function ServiceHeader() {
  const machineUrl = import.meta.env.VITE_MACHINE_APP_URL || 'http://localhost:5173';

  return (
    <header className="flex min-w-0 items-center justify-between rounded-2xl bg-white px-6 py-5 shadow-sm">
      <h1 className="font-display text-2xl font-bold tracking-tight text-slate-900">
        PALACÍN
        <span className="ml-2 align-middle text-xs font-normal tracking-widest text-slate-500 uppercase">
          Service Mode
        </span>
      </h1>

        <a href={machineUrl}
        className="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700
                   transition-colors hover:bg-slate-100
                   focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
      >
        ← Customer view
      </a>
    </header>
  );
}
