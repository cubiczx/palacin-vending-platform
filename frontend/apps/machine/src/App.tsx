import { useMachineState } from './hooks/useMachineState';
import { BalanceDisplay } from './components/BalanceDisplay';
import { ProductGrid } from './components/ProductGrid';
import { CoinInsertionPanel } from './components/CoinInsertionPanel';
import { ReturnCoinsButton } from './components/ReturnCoinsButton';
import { OutputTray } from './components/OutputTray';
import './index.css'
import './App.scss';

export default function App() {
  const { state, loading, busy, feedback, insertCoin, selectProduct, returnCoins } = useMachineState();

  if (loading) {
    return (
      <main className="flex min-h-dvh items-center justify-center text-text-muted">
        <p>Loading machine…</p>
      </main>
    );
  }

  if (!state) {
    return (
      <main className="flex min-h-dvh items-center justify-center px-6 text-center text-text-muted">
        <p>Unable to reach the vending machine. Please try again later.</p>
      </main>
    );
  }

  const trayContent =
  feedback.type === 'error'
    ? { type: 'error' as const, message: feedback.message }
    : feedback.type === 'vended'
      ? {
          type: 'vended' as const,
          product: feedback.product,
          productName: state.products.find((p) => p.sku === feedback.product)?.name ?? feedback.product,
          change: feedback.change,
        }
      : feedback.type === 'returned'
        ? { type: 'returned' as const, coins: feedback.coins }
        : { type: 'empty' as const };

    return (
    <div className="mx-auto min-h-dvh max-w-4xl px-4 py-1 machine-grid" aria-busy={busy}>
      <header className="flex min-w-0 items-center justify-center gap-2.5 rounded-2xl bg-panel px-4 py-2.5">
        <svg
          className="h-10 w-10 shrink-0 text-accent"
          fill="none"
          stroke="currentColor"
          strokeWidth="1.75"
          viewBox="0 0 24 24"
          aria-hidden="true"
        >
          <rect x="5" y="2" width="14" height="20" rx="2" />
          <path d="M5 8h14M5 14h14" />
          <circle cx="15" cy="5" r="0.75" fill="currentColor" />
          <circle cx="17" cy="5" r="0.75" fill="currentColor" />
          <rect x="8" y="17" width="8" height="3" rx="1" />
        </svg>

        <div className="flex flex-col justify-center leading-none">
          <h1 className="font-display text-lg font-bold tracking-wider text-accent uppercase">
            PALACÍN
          </h1>
          <span className="mt-0.5 font-display text-base font-bold tracking-wide text-text-primary uppercase">
            Vending
          </span>
        </div>
      </header>

      <section aria-label="Available products" className="products-column min-w-0">
        <h2 className="mb-3 text-xs font-semibold tracking-widest text-text-muted uppercase">Products</h2>
        <ProductGrid products={state.products} balance={state.insertedAmount} busy={busy} onSelect={selectProduct} />
      </section>

      <section aria-label="Balance and coin controls" className="panel-column min-w-0 rounded-2xl bg-panel-alt/30 p-4">
        <BalanceDisplay amount={state.insertedAmount} />
        <CoinInsertionPanel onInsert={insertCoin} disabled={busy} />
        <ReturnCoinsButton disabled={busy || state.insertedAmount === 0} amount={state.insertedAmount} onClick={returnCoins} />
        <OutputTray content={trayContent} />
      </section>
    </div>
  );
}
