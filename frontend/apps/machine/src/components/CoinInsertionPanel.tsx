import { ACCEPTED_COINS } from '../types/machine';

interface Props {
  onInsert: (cents: number) => void;
  disabled: boolean;
}

export function CoinInsertionPanel({ onInsert, disabled }: Props) {
  return (
    <fieldset disabled={disabled} className="coin-panel mt-6">
      <legend className="mb-3 text-xs font-semibold tracking-widest text-text-muted uppercase">
        Insert coins
      </legend>
      <div className="grid grid-cols-4 gap-3">
        {ACCEPTED_COINS.map((coin) => (
          <button
            key={coin.cents}
            type="button"
            onClick={() => onInsert(coin.cents)}
            aria-label={`Insert ${(coin.cents / 100).toFixed(2)} euros`}
            className="aspect-square rounded-full border-2 border-accent/50 font-display font-bold text-accent
                       transition-all hover:scale-110 hover:border-accent hover:shadow-[0_0_16px_-2px_var(--color-accent)]
                       active:scale-95
                       disabled:pointer-events-none disabled:opacity-30
                       focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
          >
            {coin.label}
          </button>
        ))}
      </div>
    </fieldset>
  );
}
