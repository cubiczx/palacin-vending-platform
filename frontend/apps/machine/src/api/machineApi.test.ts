import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { machineApi } from './machineApi';

describe('machineApi', () => {
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

  it('getState calls GET /machine/state', async () => {
    await machineApi.getState();
    expect(fetch).toHaveBeenCalledWith('/api/machine/state', expect.objectContaining({ signal: undefined }));
  });

  it('insertCoin posts the cents value as JSON', async () => {
    await machineApi.insertCoin(25);
    expect(fetch).toHaveBeenCalledWith(
      '/api/machine/coins',
      expect.objectContaining({ method: 'POST', body: JSON.stringify({ cents: 25 }) }),
    );
  });

  it('selectProduct lowercases the sku in the URL', async () => {
    await machineApi.selectProduct('SODA');
    expect(fetch).toHaveBeenCalledWith('/api/machine/select/soda', expect.objectContaining({ method: 'POST' }));
  });

  it('returnCoins posts to /machine/return', async () => {
    await machineApi.returnCoins();
    expect(fetch).toHaveBeenCalledWith('/api/machine/return', expect.objectContaining({ method: 'POST' }));
  });

  it('forwards an AbortSignal through to fetch', async () => {
    const controller = new AbortController();
    await machineApi.getState(controller.signal);
    expect(fetch).toHaveBeenCalledWith('/api/machine/state', expect.objectContaining({ signal: controller.signal }));
  });
});
