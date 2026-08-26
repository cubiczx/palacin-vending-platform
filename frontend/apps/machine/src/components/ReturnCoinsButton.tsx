interface Props {
  disabled: boolean;
  amount: number;
  onClick: () => void;
}

export function ReturnCoinsButton({ disabled, amount = 0, onClick }: Props) {
  const safeAmount = Number.isFinite(amount) ? amount : 0;

  return (
    <button
      type="button"
      className="return-button"
      disabled={disabled}
      onClick={onClick}
      aria-label={disabled || safeAmount === 0 ? 'Return coins' : `Return ${safeAmount.toFixed(2)} euros`}
    >
      Return coins
    </button>
  );
}
