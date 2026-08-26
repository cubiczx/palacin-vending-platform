interface Props {
  disabled: boolean;
  amount: number;
  onClick: () => void;
}

export function ReturnCoinsButton({ disabled, amount, onClick }: Props) {
  return (
    <button
      type="button"
      disabled={disabled}
      onClick={onClick}
      aria-label={disabled ? 'Return coins' : `Return ${amount.toFixed(2)} euros`}
      className="return-button mt-6 flex w-full items-center justify-center gap-2 rounded-xl border border-accent/40 bg-panel-alt py-3 font-semibold text-accent
                 transition-colors hover:bg-panel-alt/70
                 disabled:pointer-events-none disabled:opacity-40
                 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
    >
      <svg
        className="h-5 w-5 shrink-0 stroke-current"
        fill="none"
        strokeWidth="2"
        viewBox="0 0 24 24"
        aria-hidden="true"
      >
        <path strokeLinecap="round" strokeLinejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
      </svg>
      <span>Return coins</span>
    </button>
  );
}
