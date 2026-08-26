import type { Product, ProductSku } from '../types/machine';

interface Props {
  products: Product[];
  balance: number;
  busy: boolean;
  onSelect: (sku: ProductSku) => void;
}

export function ProductGrid({ products, balance, busy, onSelect }: Props) {
  return (
    <div className="product-grid" role="list">
      {products.map((product) => {
        const affordable = balance >= product.price;
        const disabled = busy || !product.inStock;

        return (
          <button
            key={product.sku}
            type="button"
            role="listitem"
            className="product-card"
            data-out-of-stock={!product.inStock}
            disabled={disabled}
            onClick={() => onSelect(product.sku)}
            aria-label={`${product.name}, ${product.price.toFixed(2)} euros${!product.inStock ? ', out of stock' : ''}`}
          >
            <span className="product-card__name">{product.name}</span>
            <span className="product-card__price">{product.price.toFixed(2)}€</span>
            {!product.inStock && <span className="product-card__badge">Sold out</span>}
            {product.inStock && !affordable && (
              <span className="product-card__hint">
                Insert {(product.price - balance).toFixed(2)}€ more
              </span>
            )}
          </button>
        );
      })}
    </div>
  );
}
