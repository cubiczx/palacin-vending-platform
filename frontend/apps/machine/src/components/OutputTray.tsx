import type { CoinCounts, ProductSku } from '../types/machine';
import { ProductTray } from './ProductTray';
import { CoinTray } from './CoinTray';

type TrayContent =
  | { type: 'empty' }
  | { type: 'vended'; product: ProductSku; productName: string; change: CoinCounts }
  | { type: 'returned'; coins: CoinCounts }
  | { type: 'error'; message: string };

export function OutputTray({ content }: { content: TrayContent }) {
  const isError = content.type === 'error';
  const isEmpty = content.type === 'empty';

  return (
    <div className="output-tray mt-6">
      <div
        role="status"
        aria-live="polite"
        className={`flex min-h-[4.5rem] flex-col items-center justify-center rounded-2xl border-2 border-dashed p-3 text-center transition-all ${
          isError
            ? 'border-error/40 bg-error/10'
            : isEmpty
              ? 'border-accent/40 bg-black/40'
              : 'border-accent/60 bg-accent/5'
        }`}
      >
        {isEmpty && (
          <div>
            <p className="text-sm font-semibold text-text-muted">— Empty —</p>
            <p className="mt-0.5 text-xs text-text-muted/70">Your item will dispense here</p>
          </div>
        )}

        {content.type === 'vended' && (
          <div className="flex flex-wrap items-center justify-center gap-2">
            <ProductTray sku={content.product} name={content.productName} />
            <CoinTray coins={content.change} label="Change" emptyLabel="No change due" />
          </div>
        )}

        {content.type === 'returned' && (
          <div className="flex items-center justify-center">
            <CoinTray coins={content.coins} label="Returned" emptyLabel="Nothing to return" />
          </div>
        )}

        {isError && <p className="text-sm text-error">{content.message}</p>}
      </div>
    </div>
  );
}
