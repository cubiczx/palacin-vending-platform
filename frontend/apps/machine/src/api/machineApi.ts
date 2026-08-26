import { apiRequest } from './client';
import type { MachineState, ProductSku, ReturnResult, VendResult } from '../types/machine';

export const machineApi = {
  getState: (signal?: AbortSignal): Promise<MachineState> => apiRequest('/machine/state', { signal }),

  insertCoin: (cents: number, signal?: AbortSignal): Promise<{ insertedAmount: number }> =>
    apiRequest('/machine/coins', {
      method: 'POST',
      body: JSON.stringify({ cents }),
      signal,
    }),

  selectProduct: (sku: ProductSku, signal?: AbortSignal): Promise<VendResult> =>
    apiRequest(`/machine/select/${sku.toLowerCase()}`, { method: 'POST', signal, }),

  returnCoins: (signal?: AbortSignal): Promise<ReturnResult> => apiRequest('/machine/return', { method: 'POST', signal, }),
};
