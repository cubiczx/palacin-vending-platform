import type { Product, ProductSku } from '../types/machine';
import { PRODUCT_VISUALS } from '../types/machine';

interface Props {
  products: Product[];
  balance: number;
  busy: boolean;
  onSelect: (sku: ProductSku) => void;
}

export function ProductGrid({ products, balance, busy, onSelect }: Props) {
  return (
    <div role="list" className="flex flex-col gap-3">
      {products.map((product) => {
        const visual = PRODUCT_VISUALS[product.sku];
        const affordable = balance >= product.price;
        const disabled = busy || !product.inStock;

        return (
          <div
            key={product.sku}
            role="listitem"
            className="flex items-center gap-4 rounded-2xl bg-panel p-4 transition-opacity data-disabled:opacity-50"
            data-disabled={!product.inStock || undefined}
          >
            <div
              className={`flex h-14 w-14 shrink-0 items-center justify-center rounded-xl text-2xl ring-1 ${visual.colorClass} ${visual.ringClass}`}
              aria-hidden="true"
            >
              {visual.emoji}
            </div>

            <div className="min-w-0 flex-1">
              <p className="font-display font-medium">{product.name}</p>
              <p className="font-display text-lg font-bold text-accent">{product.price.toFixed(2)}€</p>
              {!product.inStock && (
                <span className="inline-block rounded-full bg-error/15 px-2 py-0.5 text-xs font-semibold text-error">
                  Sold out
                </span>
              )}
              {product.inStock && !affordable && (
                <p className="text-xs text-text-muted">Insert {(product.price - balance).toFixed(2)}€ more</p>
              )}
            </div>

            <button
              type="button"
              disabled={disabled}
              onClick={() => onSelect(product.sku)}
              aria-label={`Buy ${product.name}, ${product.price.toFixed(2)} euros${!product.inStock ? ', out of stock' : ''}`}
              className="shrink-0 rounded-xl bg-accent px-5 py-2 font-display font-bold text-bg
                         transition-transform hover:scale-105 active:scale-95
                         disabled:pointer-events-none disabled:opacity-40
                         focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
            >
              Buy
            </button>
          </div>
        );
      })}
    </div>
  );
}
