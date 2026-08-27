import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { serviceApi } from './serviceApi';

describe('serviceApi', () => {
  beforeEach(() => {
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue({
      ok: true,
      status: 200,
      json: async () => ({}),
    }));
  });

  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it('getState calls GET /service/state', async () => {
    await serviceApi.getState();
    expect(fetch).toHaveBeenCalledWith('/api/service/state', expect.objectContaining({ signal: undefined }));
  });

  it('restock lowercases the sku and posts the quantity', async () => {
    await serviceApi.restock('SODA', 10);
    expect(fetch).toHaveBeenCalledWith(
      '/api/service/products/soda/restock',
      expect.objectContaining({ method: 'POST', body: JSON.stringify({ quantity: 10 }) }),
    );
  });

  it('updatePrice lowercases the sku and PATCHes the price', async () => {
    await serviceApi.updatePrice('WATER', 0.75);
    expect(fetch).toHaveBeenCalledWith(
      '/api/service/products/water/price',
      expect.objectContaining({ method: 'PATCH', body: JSON.stringify({ price: 0.75 }) }),
    );
  });

  it('setChangeInventory posts the full counts map', async () => {
    const counts = { 5: 40, 10: 40, 25: 40, 100: 40 };
    await serviceApi.setChangeInventory(counts);
    expect(fetch).toHaveBeenCalledWith(
      '/api/service/change',
      expect.objectContaining({ method: 'POST', body: JSON.stringify({ counts }) }),
    );
  });

  it('getTransactions builds the query string from page and perPage', async () => {
    await serviceApi.getTransactions(2, 5);
    expect(fetch).toHaveBeenCalledWith('/api/service/transactions?page=2&perPage=5', expect.anything());
  });

  it('getTransactions defaults to page 1 and perPage 10', async () => {
    await serviceApi.getTransactions();
    expect(fetch).toHaveBeenCalledWith('/api/service/transactions?page=1&perPage=10', expect.anything());
  });

  it('forwards an AbortSignal through to fetch', async () => {
    const controller = new AbortController();
    await serviceApi.getState(controller.signal);
    expect(fetch).toHaveBeenCalledWith('/api/service/state', expect.objectContaining({ signal: controller.signal }));
  });
});
