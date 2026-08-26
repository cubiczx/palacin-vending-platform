import { useServiceState } from './hooks/useServiceState';
import { ServiceHeader } from './components/ServiceHeader';
import { ProductServiceCard } from './components/ProductServiceCard';
import { ChangeInventoryForm } from './components/ChangeInventoryForm';
import { TransactionHistoryTable } from './components/TransactionHistoryTable';
import { Toast } from './components/Toast';
import './App.css';

export default function App() {
  const { state, transactions, loading, busy, toast, restock, updatePrice, setChangeInventory } = useServiceState();

  if (loading) {
    return (
      <main className="flex min-h-dvh items-center justify-center text-slate-500">
        <p>Loading service panel…</p>
      </main>
    );
  }

  if (!state) {
    return (
      <main className="flex min-h-dvh items-center justify-center px-6 text-center text-slate-500">
        <p>Unable to reach the service panel. Please try again later.</p>
      </main>
    );
  }

  return (
    <div className="mx-auto min-h-dvh max-w-5xl space-y-4 px-4 py-6" aria-busy={busy}>
      <ServiceHeader />

      <Toast toast={toast} />

      <section aria-label="Products">
        <h2 className="mb-3 text-xs font-semibold tracking-widest text-slate-500 uppercase">Products</h2>
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {state.products.map((product) => (
            <ProductServiceCard
              key={product.sku}
              product={product}
              busy={busy}
              onRestock={(quantity) => restock(product.sku, quantity)}
              onUpdatePrice={(price) => updatePrice(product.sku, price)}
            />
          ))}
        </div>
      </section>

      <section aria-label="Change inventory">
        <h2 className="mb-3 text-xs font-semibold tracking-widest text-slate-500 uppercase">Change</h2>
        <ChangeInventoryForm coins={state.changeInventory.coins} busy={busy} onSave={setChangeInventory} />
      </section>

      <section aria-label="Recent sales">
        <h2 className="mb-3 text-xs font-semibold tracking-widest text-slate-500 uppercase">Recent sales</h2>
        <TransactionHistoryTable transactions={transactions} />
      </section>
    </div>
  );
}
