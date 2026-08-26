import { useCallback, useEffect, useState } from 'react';
import { ApiError } from '../api/client';
import { machineApi } from '../api/machineApi';
import type { CoinCounts, MachineState, ProductSku } from '../types/machine';

type Feedback =
  | { type: 'idle' }
  | { type: 'vended'; product: ProductSku; change: CoinCounts }
  | { type: 'returned'; coins: CoinCounts }
  | { type: 'error'; code: string; message: string };

export function useMachineState() {
  const [state, setState] = useState<MachineState | null>(null);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);
  const [feedback, setFeedback] = useState<Feedback>({ type: 'idle' });

  const refresh = useCallback(async () => {
    try {
      const data = await machineApi.getState();
      setState(data);
    } catch (err) {
      if (err instanceof ApiError) {
        setFeedback({ type: 'error', code: err.code, message: err.message });
      }
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    refresh();
  }, [refresh]);

  const runAction = useCallback(
    async (action: () => Promise<void>) => {
      setBusy(true);
      setFeedback({ type: 'idle' });
      try {
        await action();
      } catch (err) {
        if (err instanceof ApiError) {
          setFeedback({ type: 'error', code: err.code, message: err.message });
        } else {
          setFeedback({ type: 'error', code: 'UNKNOWN_ERROR', message: 'Something went wrong.' });
        }
      } finally {
        setBusy(false);
      }
    },
    [],
  );

  const insertCoin = useCallback(
    (cents: number) =>
      runAction(async () => {
        const { insertedAmount } = await machineApi.insertCoin(cents);
        setState((prev) => (prev ? { ...prev, insertedAmount } : prev));
      }),
    [runAction],
  );

  const selectProduct = useCallback(
    (sku: ProductSku) =>
      runAction(async () => {
        const result = await machineApi.selectProduct(sku);
        setFeedback({ type: 'vended', product: result.product, change: result.change.coins });
        await refresh();
      }),
    [runAction, refresh],
  );

  const returnCoins = useCallback(
    () =>
      runAction(async () => {
        const result = await machineApi.returnCoins();
        setFeedback({ type: 'returned', coins: result.coins });
        await refresh();
      }),
    [runAction, refresh],
  );

  return { state, loading, busy, feedback, insertCoin, selectProduct, returnCoins };
}
