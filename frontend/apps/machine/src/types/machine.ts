export type ProductSku = 'WATER' | 'JUICE' | 'SODA';

export interface Product {
  sku: ProductSku;
  name: string;
  price: number;
  inStock: boolean;
}

export interface MachineState {
  products: Product[];
  insertedAmount: number;
}

export type CoinCounts = Record<string, number>;

export interface VendResult {
  product: ProductSku;
  change: { coins: CoinCounts };
}

export interface ReturnResult {
  coins: CoinCounts;
}

export interface ApiErrorBody {
  error: string;
  message: string;
}

export const ACCEPTED_COINS: ReadonlyArray<{ label: string; cents: number }> = [
  { label: '0.05€', cents: 5 },
  { label: '0.10€', cents: 10 },
  { label: '0.25€', cents: 25 },
  { label: '1€', cents: 100 },
];

export const PRODUCT_VISUALS: Record<ProductSku, { emoji: string; colorClass: string; ringClass: string }> = {
  WATER: { emoji: '💧', colorClass: 'bg-water/10', ringClass: 'ring-water/30' },
  JUICE: { emoji: '🧃', colorClass: 'bg-juice/10', ringClass: 'ring-juice/30' },
  SODA: { emoji: '🥫', colorClass: 'bg-soda/10', ringClass: 'ring-soda/30' },
};
