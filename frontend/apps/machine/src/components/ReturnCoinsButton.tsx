interface Props {
  disabled: boolean;
  onClick: () => void;
}

export function ReturnCoinsButton({ disabled, onClick }: Props) {
  return (
    <button type="button" className="return-button" disabled={disabled} onClick={onClick}>
      Return coins
    </button>
  );
}
