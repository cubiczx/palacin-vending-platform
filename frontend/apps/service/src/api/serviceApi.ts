import { apiRequest } from './client';
import type { FullMachineState, ProductSku, TransactionHistory } from '../types/service';

export const serviceApi = {
  getState: (signal?: AbortSignal): Promise<FullMachineState> =>
    apiRequest('/service/state', { signal }),

  restock: (sku: ProductSku, quantity: number, signal?: AbortSignal): Promise<void> =>
    apiRequest(`/service/products/${sku.toLowerCase()}/restock`, {
      method: 'POST',
      body: JSON.stringify({ quantity }),
      signal,
    }),

  updatePrice: (sku: ProductSku, price: number, signal?: AbortSignal): Promise<void> =>
    apiRequest(`/service/products/${sku.toLowerCase()}/price`, {
      method: 'PATCH',
      body: JSON.stringify({ price }),
      signal,
    }),

  setChangeInventory: (counts: Record<number, number>, signal?: AbortSignal): Promise<void> =>
    apiRequest('/service/change', {
      method: 'POST',
      body: JSON.stringify({ counts }),
      signal,
    }),

  getTransactions: (page = 1, perPage = 10, signal?: AbortSignal): Promise<TransactionHistory> =>
    apiRequest(`/service/transactions?page=${page}&perPage=${perPage}`, { signal }),
};
