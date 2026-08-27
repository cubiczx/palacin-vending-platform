import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { renderHook, waitFor, act } from '@testing-library/react';
import { useMachineState } from './useMachineState';

const stateResponse = {
  products: [{ sku: 'WATER', name: 'Water', price: 0.65, inStock: true }],
  insertedAmount: 0,
};

function mockFetchOnce(body: unknown, ok = true, status = 200) {
  (fetch as ReturnType<typeof vi.fn>).mockResolvedValueOnce({
    ok,
    status,
    json: async () => body,
  });
}

describe('useMachineState', () => {
  beforeEach(() => {
    vi.stubGlobal('fetch', vi.fn());
  });

  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it('loads the initial machine state on mount', async () => {
    mockFetchOnce(stateResponse);
    const { result } = renderHook(() => useMachineState());

    expect(result.current.loading).toBe(true);

    await waitFor(() => expect(result.current.loading).toBe(false));
    expect(result.current.state).toEqual(stateResponse);
  });

  it('sets an error feedback if the initial load fails', async () => {
    mockFetchOnce({ error: 'SERVER_ERROR', message: 'Boom.' }, false, 500);
    const { result } = renderHook(() => useMachineState());

    await waitFor(() => expect(result.current.loading).toBe(false));
    expect(result.current.feedback).toEqual({ type: 'error', code: 'SERVER_ERROR', message: 'Boom.' });
  });

  it('insertCoin updates the balance optimistically from the response', async () => {
    mockFetchOnce(stateResponse);
    const { result } = renderHook(() => useMachineState());
    await waitFor(() => expect(result.current.loading).toBe(false));

    mockFetchOnce({ insertedAmount: 0.25 });
    await act(async () => {
      await result.current.insertCoin(25);
    });

    expect(result.current.state?.insertedAmount).toBe(0.25);
    expect(result.current.busy).toBe(false);
  });

  it('selectProduct sets vended feedback and refreshes state', async () => {
    mockFetchOnce(stateResponse);
    const { result } = renderHook(() => useMachineState());
    await waitFor(() => expect(result.current.loading).toBe(false));

    mockFetchOnce({ product: 'WATER', change: { coins: { '0.25': 1 } } });
    mockFetchOnce({ ...stateResponse, insertedAmount: 0 });
    await act(async () => {
      await result.current.selectProduct('WATER');
    });

    expect(result.current.feedback).toEqual({
      type: 'vended',
      product: 'WATER',
      change: { '0.25': 1 },
    });
  });

  it('selectProduct sets error feedback when the purchase fails, without refreshing', async () => {
    mockFetchOnce(stateResponse);
    const { result } = renderHook(() => useMachineState());
    await waitFor(() => expect(result.current.loading).toBe(false));

    mockFetchOnce({ error: 'INSUFFICIENT_FUNDS', message: 'Not enough.' }, false, 402);
    await act(async () => {
      await result.current.selectProduct('SODA');
    });

    expect(result.current.feedback).toEqual({
      type: 'error',
      code: 'INSUFFICIENT_FUNDS',
      message: 'Not enough.',
    });
  });

  it('returnCoins sets returned feedback and refreshes state', async () => {
    mockFetchOnce(stateResponse);
    const { result } = renderHook(() => useMachineState());
    await waitFor(() => expect(result.current.loading).toBe(false));

    mockFetchOnce({ coins: { '0.10': 2 } });
    mockFetchOnce({ ...stateResponse, insertedAmount: 0 });
    await act(async () => {
      await result.current.returnCoins();
    });

    expect(result.current.feedback).toEqual({ type: 'returned', coins: { '0.10': 2 } });
  });

  it('sets busy to true while an action is in flight', async () => {
    mockFetchOnce(stateResponse);
    const { result } = renderHook(() => useMachineState());
    await waitFor(() => expect(result.current.loading).toBe(false));

    let resolveFetch: (value: unknown) => void = () => {};
    (fetch as ReturnType<typeof vi.fn>).mockReturnValueOnce(
      new Promise((resolve) => {
        resolveFetch = resolve;
      }),
    );

    let pending!: Promise<void>;
    act(() => {
      pending = result.current.insertCoin(5);
    });

    await waitFor(() => expect(result.current.busy).toBe(true));

    resolveFetch({ ok: true, status: 200, json: async () => ({ insertedAmount: 0.05 }) });
    await act(async () => {
      await pending;
    });

    expect(result.current.busy).toBe(false);
  });
});
