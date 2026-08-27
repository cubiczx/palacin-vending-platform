import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { apiRequest, ApiError } from './client';

describe('apiRequest', () => {
  beforeEach(() => {
    vi.stubGlobal('fetch', vi.fn());
  });

  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it('prefixes the path with /api and returns parsed JSON on success', async () => {
    const mockJson = { hello: 'world' };
    (fetch as ReturnType<typeof vi.fn>).mockResolvedValue({
      ok: true,
      status: 200,
      json: async () => mockJson,
    });

    const result = await apiRequest<typeof mockJson>('/machine/state');

    expect(fetch).toHaveBeenCalledWith(
      '/api/machine/state',
      expect.objectContaining({ headers: { 'Content-Type': 'application/json' } }),
    );
    expect(result).toEqual(mockJson);
  });

  it('returns undefined for a 204 response without parsing a body', async () => {
    const jsonSpy = vi.fn();
    (fetch as ReturnType<typeof vi.fn>).mockResolvedValue({
      ok: true,
      status: 204,
      json: jsonSpy,
    });

    const result = await apiRequest('/service/products/soda/restock', { method: 'POST' });

    expect(result).toBeUndefined();
    expect(jsonSpy).not.toHaveBeenCalled();
  });

  it('throws an ApiError built from the response body when the request fails', async () => {
    (fetch as ReturnType<typeof vi.fn>).mockResolvedValue({
      ok: false,
      status: 402,
      json: async () => ({ error: 'INSUFFICIENT_FUNDS', message: 'Insufficient funds: inserted 0.25, price is 1.50.' }),
    });

    await expect(apiRequest('/machine/select/soda', { method: 'POST' })).rejects.toMatchObject({
      code: 'INSUFFICIENT_FUNDS',
      message: 'Insufficient funds: inserted 0.25, price is 1.50.',
      status: 402,
    });
  });

  it('throws an ApiError instance specifically, not just a plain object', async () => {
    (fetch as ReturnType<typeof vi.fn>).mockResolvedValue({
      ok: false,
      status: 404,
      json: async () => ({ error: 'PRODUCT_NOT_FOUND', message: 'Not found.' }),
    });

    await expect(apiRequest('/machine/select/cola', { method: 'POST' })).rejects.toBeInstanceOf(ApiError);
  });

  it('falls back to a generic UNKNOWN_ERROR when the error body is not valid JSON', async () => {
    (fetch as ReturnType<typeof vi.fn>).mockResolvedValue({
      ok: false,
      status: 500,
      json: async () => {
        throw new Error('not json');
      },
    });

    await expect(apiRequest('/machine/state')).rejects.toMatchObject({
      code: 'UNKNOWN_ERROR',
      status: 500,
    });
  });

  it('merges custom init options (method, body, signal) into the fetch call', async () => {
    (fetch as ReturnType<typeof vi.fn>).mockResolvedValue({
      ok: true,
      status: 200,
      json: async () => ({}),
    });
    const controller = new AbortController();

    await apiRequest('/machine/coins', {
      method: 'POST',
      body: JSON.stringify({ cents: 25 }),
      signal: controller.signal,
    });

    expect(fetch).toHaveBeenCalledWith(
      '/api/machine/coins',
      expect.objectContaining({
        method: 'POST',
        body: JSON.stringify({ cents: 25 }),
        signal: controller.signal,
      }),
    );
  });
});
