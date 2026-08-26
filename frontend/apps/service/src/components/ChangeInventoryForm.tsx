import { useEffect, useState, type FormEvent } from 'react';
import { DENOMINATIONS } from '../types/service';

interface Props {
  coins: Record<string, number>;
  busy: boolean;
  onSave: (counts: Record<number, number>) => void;
}

const LABELS: Record<number, string> = { 5: '0.05€', 10: '0.10€', 25: '0.25€', 100: '1€' };

export function ChangeInventoryForm({ coins, busy, onSave }: Props) {
  const [values, setValues] = useState<Record<number, string>>({});

  useEffect(() => {
    const next: Record<number, string> = {};
    for (const cents of DENOMINATIONS) {
      next[cents] = String(coins[(cents / 100).toFixed(2)] ?? 0);
    }
    setValues(next);
  }, [coins]);

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    const counts: Record<number, number> = {};
    for (const cents of DENOMINATIONS) {
      const parsed = Number(values[cents]);
      counts[cents] = Number.isFinite(parsed) && parsed >= 0 ? parsed : 0;
    }
    onSave(counts);
  };

  return (
    <form onSubmit={handleSubmit} className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <h2 className="font-display font-semibold text-slate-900">Change inventory</h2>
      <p className="mt-1 mb-4 text-xs text-slate-500">
        Saving replaces the full coin inventory with these exact counts — it does not add to the current stock.
      </p>
      <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
        {DENOMINATIONS.map((cents) => (
          <div key={cents}>
            <label htmlFor={`coin-${cents}`} className="mb-1 block text-xs font-medium text-slate-600">
              {LABELS[cents]}
            </label>
            <input
              id={`coin-${cents}`}
              type="number"
              min={0}
              value={values[cents] ?? ''}
              onChange={(e) => setValues((prev) => ({ ...prev, [cents]: e.target.value }))}
              disabled={busy}
              className="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm
                         focus-visible:outline focus-visible:outline-2 focus-visible:outline-accent disabled:opacity-50"
            />
          </div>
        ))}
      </div>
      <button
        type="submit"
        disabled={busy}
        className="mt-4 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white
                   transition-colors hover:bg-slate-700 disabled:opacity-40
                   focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
      >
        Save full inventory
      </button>
    </form>
  );
}
