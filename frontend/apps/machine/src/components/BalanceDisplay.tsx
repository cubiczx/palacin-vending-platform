interface Props {
  amount: number;
}

export function BalanceDisplay({ amount }: Props) {
  return (
    <div className="balance-box rounded-2xl bg-panel p-6 text-center">
      <p className="text-xs font-semibold tracking-widest text-text-muted uppercase">Current balance</p>
      <p
        aria-live="polite"
        className="balance-amount mt-2 rounded-xl border-2 border-accent/60 py-4 font-display text-5xl font-bold text-accent shadow-[0_0_24px_-4px_var(--color-accent)]"
      >
        {amount.toFixed(2)}€
      </p>
    </div>
  );
}
