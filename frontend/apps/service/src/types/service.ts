export type ProductSku = 'WATER' | 'JUICE' | 'SODA';

export interface ServiceProduct {
  sku: ProductSku;
  name: string;
  price: number;
  stock: number;
}

export interface ChangeInventory {
  coins: Record<string, number>;
}

export interface FullMachineState {
  products: ServiceProduct[];
  changeInventory: ChangeInventory;
}

export interface Transaction {
  id: string | null;
  product: ProductSku;
  price: number;
  amountInserted: number;
  changeReturned: { coins: Record<string, number> };
  occurredAt: string;
}

export interface TransactionHistory {
  items: Transaction[];
  total: number;
}

export interface ApiErrorBody {
  error: string;
  message: string;
}

export const DENOMINATIONS = [5, 10, 25, 100] as const;

export const PRODUCT_VISUALS: Record<ProductSku, { emoji: string }> = {
  WATER: { emoji: '💧' },
  JUICE: { emoji: '🧃' },
  SODA: { emoji: '🥫' },
};
