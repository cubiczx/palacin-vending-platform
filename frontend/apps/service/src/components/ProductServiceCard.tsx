import { useState, type FormEvent } from 'react';
import type { ServiceProduct } from '../types/service';
import { PRODUCT_VISUALS } from '../types/service';

interface Props {
  product: ServiceProduct;
  busy: boolean;
  onRestock: (quantity: number) => void;
  onUpdatePrice: (price: number) => void;
}

export function ProductServiceCard({ product, busy, onRestock, onUpdatePrice }: Props) {
  const [quantity, setQuantity] = useState('10');
  const [price, setPrice] = useState(product.price.toFixed(2));

  const handleRestock = (e: FormEvent) => {
    e.preventDefault();
    const parsed = Number(quantity);
    if (Number.isFinite(parsed) && parsed > 0) onRestock(parsed);
  };

  const handlePrice = (e: FormEvent) => {
    e.preventDefault();
    const parsed = Number(price);
    if (Number.isFinite(parsed) && parsed >= 0) onUpdatePrice(parsed);
  };

  return (
    <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <div className="flex items-center gap-3">
        <span aria-hidden="true" className="text-2xl">
          {PRODUCT_VISUALS[product.sku].emoji}
        </span>
        <div>
          <p className="font-display font-semibold text-slate-900">{product.name}</p>
          <p className="text-sm text-slate-500">
            Stock: <span className={product.stock === 0 ? 'font-semibold text-red-600' : 'font-semibold text-slate-700'}>{product.stock}</span>
            {' · '}
            {product.price.toFixed(2)}€
          </p>
        </div>
      </div>

      <form onSubmit={handleRestock} className="mt-4 flex items-end gap-2">
        <div className="flex-1">
          <label htmlFor={`restock-${product.sku}`} className="mb-1 block text-xs font-medium text-slate-600">
            Add units
          </label>
          <input
            id={`restock-${product.sku}`}
            type="number"
            min={1}
            value={quantity}
            onChange={(e) => setQuantity(e.target.value)}
            disabled={busy}
            className="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm
                       focus-visible:outline focus-visible:outline-2 focus-visible:outline-accent disabled:opacity-50"
          />
        </div>
        <button
          type="submit"
          disabled={busy}
          className="rounded-lg bg-slate-900 px-3 py-1.5 text-sm font-semibold text-white
                     transition-colors hover:bg-slate-700 disabled:opacity-40
                     focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
        >
          Restock
        </button>
      </form>

      <form onSubmit={handlePrice} className="mt-3 flex items-end gap-2">
        <div className="flex-1">
          <label htmlFor={`price-${product.sku}`} className="mb-1 block text-xs font-medium text-slate-600">
            New price (€)
          </label>
          <input
            id={`price-${product.sku}`}
            type="number"
            min={0}
            step={0.05}
            value={price}
            onChange={(e) => setPrice(e.target.value)}
            disabled={busy}
            className="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm
                       focus-visible:outline focus-visible:outline-2 focus-visible:outline-accent disabled:opacity-50"
          />
        </div>
        <button
          type="submit"
          disabled={busy}
          className="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-semibold text-slate-700
                     transition-colors hover:bg-slate-100 disabled:opacity-40
                     focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
        >
          Update
        </button>
      </form>
    </div>
  );
}
