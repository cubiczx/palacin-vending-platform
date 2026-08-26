import { ACCEPTED_COINS } from '../types/machine';

interface Props {
  onInsert: (cents: number) => void;
  disabled: boolean;
}

export function CoinInsertionPanel({ onInsert, disabled }: Props) {
  return (
    <fieldset className="coin-panel" disabled={disabled}>
      <legend>Insert coins</legend>
      <div className="coin-panel__buttons">
        {ACCEPTED_COINS.map((coin) => (
          <button
            key={coin.cents}
            type="button"
            className="coin-button"
            onClick={() => onInsert(coin.cents)}
          >
            {coin.label}
          </button>
        ))}
      </div>
    </fieldset>
  );
}
