import type { CoinCounts } from '../types/machine';

interface Props {
  coins: CoinCounts;
  label: string;
  emptyLabel: string;
}

function coinEntries(coins: CoinCounts): Array<{ value: string; key: number }> {
  return Object.entries(coins)
    .filter(([, quantity]) => quantity > 0)
    .flatMap(([value, quantity]) => Array.from({ length: quantity }, (_, i) => ({ value, key: i })));
}

export function CoinTray({ coins, label, emptyLabel }: Props) {
  const entries = coinEntries(coins);

  return (
    <div className="flex flex-wrap items-center gap-1.5">
      <span className="mr-1 shrink-0 text-[10px] tracking-wide text-text-muted uppercase">{label}</span>
      {entries.length === 0 ? (
        <span className="text-xs text-text-muted">{emptyLabel}</span>
      ) : (
        entries.map((coin) => (
          <span
            key={coin.key}
            className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-accent text-[9px] font-bold text-bg"
          >
            {coin.value}
          </span>
        ))
      )}
    </div>
  );
}
