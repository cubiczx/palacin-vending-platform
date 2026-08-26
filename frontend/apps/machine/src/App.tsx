import { useMachineState } from './hooks/useMachineState';
import { BalanceDisplay } from './components/BalanceDisplay';
import { ProductGrid } from './components/ProductGrid';
import { CoinInsertionPanel } from './components/CoinInsertionPanel';
import { ReturnCoinsButton } from './components/ReturnCoinsButton';
import { FeedbackMessage } from './components/FeedbackMessage';
import './App.css';

export default function App() {
  const { state, loading, busy, feedback, insertCoin, selectProduct, returnCoins } = useMachineState();

  if (loading) {
    return (
      <main className="machine machine--loading">
        <p>Loading machine…</p>
      </main>
    );
  }

  if (!state) {
    return (
      <main className="machine machine--loading">
        <p>Unable to reach the vending machine. Please try again later.</p>
      </main>
    );
  }

  return (
    <main className="machine">
      <h1 className="machine__title">Palacin</h1>

      <BalanceDisplay amount={state.insertedAmount} />

      <div className="sr-only" aria-live="polite">
        {busy ? 'Processing…' : ''}
      </div>

      <h2 className="sr-only">Available products</h2>

      <ProductGrid
        products={state.products}
        balance={state.insertedAmount}
        busy={busy}
        onSelect={selectProduct}
      />

      <CoinInsertionPanel onInsert={insertCoin} disabled={busy} />

      <div className="actions">
        <ReturnCoinsButton disabled={busy || state.insertedAmount === 0} amount={state.insertedAmount} onClick={returnCoins} />
        <FeedbackMessage feedback={feedback} />
      </div>
    </main>
  );
}
