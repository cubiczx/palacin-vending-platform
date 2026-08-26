import { apiRequest } from './client';
import type { MachineState, ProductSku, ReturnResult, VendResult } from '../types/machine';

export const machineApi = {
  getState: (): Promise<MachineState> => apiRequest('/machine/state'),

  insertCoin: (cents: number): Promise<{ insertedAmount: number }> =>
    apiRequest('/machine/coins', {
      method: 'POST',
      body: JSON.stringify({ cents }),
    }),

  selectProduct: (sku: ProductSku): Promise<VendResult> =>
    apiRequest(`/machine/select/${sku.toLowerCase()}`, { method: 'POST' }),

  returnCoins: (): Promise<ReturnResult> => apiRequest('/machine/return', { method: 'POST' }),
};
