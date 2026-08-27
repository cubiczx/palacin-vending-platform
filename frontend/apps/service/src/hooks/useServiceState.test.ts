import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { renderHook, waitFor, act } from '@testing-library/react';
import { useServiceState } from './useServiceState';

const stateResponse = {
  products: [{ sku: 'SODA', name: 'Soda', price: 1.5, stock: 5 }],
  changeInventory: { coins: { '0.25': 20 } },
};
const historyResponse = { items: [], total: 0 };

function queueResponses(...responses: Array<{ body: unknown; ok?: boolean; status?: number }>) {
  const fetchMock = fetch as ReturnType<typeof vi.fn>;
  for (const { body, ok = true, status = 200 } of responses) {
    fetchMock.mockResolvedValueOnce({ ok, status, json: async () => body });
  }
}

describe('useServiceState', () => {
  beforeEach(() => {
    vi.stubGlobal('fetch', vi.fn());
  });

  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it('loads state and transaction history on mount', async () => {
    queueResponses({ body: stateResponse }, { body: historyResponse });
    const { result } = renderHook(() => useServiceState());

    expect(result.current.loading).toBe(true);
    await waitFor(() => expect(result.current.loading).toBe(false));

    expect(result.current.state).toEqual(stateResponse);
    expect(result.current.transactions).toEqual([]);
  });

    it('sets an error toast if the initial load fails', async () => {
    queueResponses(
      { body: { error: 'SERVER_ERROR', message: 'Boom.' }, ok: false, status: 500 }, // getState fails
      { body: historyResponse }, // getTransactions still resolves
    );
    const { result } = renderHook(() => useServiceState());

    await waitFor(() => expect(result.current.loading).toBe(false));
    expect(result.current.toast).toEqual({ type: 'error', message: 'Boom.' });
  });

  it('restock shows a success toast and reloads state', async () => {
    queueResponses({ body: stateResponse }, { body: historyResponse });
    const { result } = renderHook(() => useServiceState());
    await waitFor(() => expect(result.current.loading).toBe(false));

    queueResponses(
      { body: {} }, // restock POST
      { body: { ...stateResponse, products: [{ ...stateResponse.products[0], stock: 15 }] } }, // reload state
      { body: historyResponse }, // reload transactions
    );
    await act(async () => {
      await result.current.restock('SODA', 10);
    });

    expect(result.current.toast).toEqual({ type: 'success', message: 'Added 10 unit(s) to SODA.' });
    expect(result.current.state?.products[0].stock).toBe(15);
  });

  it('restock shows an error toast and does not reload on failure', async () => {
    queueResponses({ body: stateResponse }, { body: historyResponse });
    const { result } = renderHook(() => useServiceState());
    await waitFor(() => expect(result.current.loading).toBe(false));

    queueResponses({ body: { error: 'PRODUCT_NOT_FOUND', message: 'Not found.' }, ok: false, status: 404 });
    await act(async () => {
      await result.current.restock('SODA', 10);
    });

    expect(result.current.toast).toEqual({ type: 'error', message: 'Not found.' });
  });

  it('updatePrice shows a success toast with the formatted price', async () => {
    queueResponses({ body: stateResponse }, { body: historyResponse });
    const { result } = renderHook(() => useServiceState());
    await waitFor(() => expect(result.current.loading).toBe(false));

    queueResponses({ body: {} }, { body: stateResponse }, { body: historyResponse });
    await act(async () => {
      await result.current.updatePrice('SODA', 1.75);
    });

    expect(result.current.toast).toEqual({ type: 'success', message: 'Updated SODA price to 1.75€.' });
  });

  it('setChangeInventory shows a fixed success toast', async () => {
    queueResponses({ body: stateResponse }, { body: historyResponse });
    const { result } = renderHook(() => useServiceState());
    await waitFor(() => expect(result.current.loading).toBe(false));

    queueResponses({ body: {} }, { body: stateResponse }, { body: historyResponse });
    await act(async () => {
      await result.current.setChangeInventory({ 5: 40, 10: 40, 25: 40, 100: 40 });
    });

    expect(result.current.toast).toEqual({ type: 'success', message: 'Change inventory saved.' });
  });

  it('sets busy to true while an action is in flight', async () => {
    queueResponses({ body: stateResponse }, { body: historyResponse });
    const { result } = renderHook(() => useServiceState());
    await waitFor(() => expect(result.current.loading).toBe(false));

    let resolveFetch: (value: unknown) => void = () => {};
    (fetch as ReturnType<typeof vi.fn>).mockReturnValueOnce(
      new Promise((resolve) => {
        resolveFetch = resolve;
      }),
    );

    let pending!: Promise<void>;
    act(() => {
      pending = result.current.restock('SODA', 5);
    });

    await waitFor(() => expect(result.current.busy).toBe(true));

    resolveFetch({ ok: true, status: 204, json: async () => undefined });
    queueResponses({ body: stateResponse }, { body: historyResponse });
    await act(async () => {
      await pending;
    });

    expect(result.current.busy).toBe(false);
  });
});
