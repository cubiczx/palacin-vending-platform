import type { ProductSku } from '../types/machine';
import { PRODUCT_VISUALS } from '../types/machine';

interface Props {
  sku: ProductSku;
  name: string;
}

export function ProductTray({ sku, name }: Props) {
  return (
    <span className="flex items-center gap-1">
      <span aria-hidden="true" className="text-xl">
        {PRODUCT_VISUALS[sku].emoji}
      </span>
      <span className="sr-only">Dispensed: {name}</span>
    </span>
  );
}
