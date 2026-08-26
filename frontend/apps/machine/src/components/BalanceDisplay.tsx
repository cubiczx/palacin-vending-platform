interface Props {
  amount: number;
}

export function BalanceDisplay({ amount }: Props) {
  return (
    <div className="balance" aria-live="polite">
      <span className="balance__label">Balance</span>
      <span className="balance__amount">{amount.toFixed(2)}€</span>
    </div>
  );
}
