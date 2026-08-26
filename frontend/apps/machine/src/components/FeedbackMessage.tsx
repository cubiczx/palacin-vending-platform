import type { CoinCounts } from '../types/machine';

type Feedback =
  | { type: 'idle' }
  | { type: 'vended'; product: string; change: CoinCounts }
  | { type: 'returned'; coins: CoinCounts }
  | { type: 'error'; code: string; message: string };

function formatCoins(coins: CoinCounts): string {
  const entries = Object.entries(coins).filter(([, quantity]) => quantity > 0);
  if (entries.length === 0) {
    return 'no change';
  }
  return entries.map(([value, quantity]) => `${quantity} × ${value}€`).join(', ');
}

export function FeedbackMessage({ feedback }: { feedback: Feedback }) {
  if (feedback.type === 'idle') {
    return null;
  }

  if (feedback.type === 'vended') {
    return (
      <div className="feedback feedback--success" role="status">
        Dispensed <strong>{feedback.product}</strong>. Change returned: {formatCoins(feedback.change)}.
      </div>
    );
  }

  if (feedback.type === 'returned') {
    return (
      <div className="feedback feedback--info" role="status">
        Returned: {formatCoins(feedback.coins)}.
      </div>
    );
  }

  return (
    <div className="feedback feedback--error" role="alert">
      {feedback.message}
    </div>
  );
}
