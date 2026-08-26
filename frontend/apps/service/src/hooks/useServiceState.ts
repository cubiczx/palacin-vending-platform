import { useCallback, useEffect, useState } from 'react';
import { ApiError } from '../api/client';
import { serviceApi } from '../api/serviceApi';
import type { FullMachineState, ProductSku, Transaction } from '../types/service';

type Toast = { type: 'idle' } | { type: 'success'; message: string } | { type: 'error'; message: string };

export function useServiceState() {
  const [state, setState] = useState<FullMachineState | null>(null);
  const [transactions, setTransactions] = useState<Transaction[]>([]);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);
  const [toast, setToast] = useState<Toast>({ type: 'idle' });

  const loadAll = useCallback(async (signal?: AbortSignal) => {
    setLoading(true);
    try {
      const [stateData, historyData] = await Promise.all([
        serviceApi.getState(signal),
        serviceApi.getTransactions(1, 10, signal),
      ]);
      setState(stateData);
      setTransactions(historyData.items);
    } catch (err) {
      if ((err as DOMException)?.name === 'AbortError') return;
      if (err instanceof ApiError) setToast({ type: 'error', message: err.message });
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    const controller = new AbortController();
    loadAll(controller.signal);
    return () => controller.abort();
  }, [loadAll]);

  const runAction = useCallback(async (action: () => Promise<void>, successMessage: string) => {
    setBusy(true);
    setToast({ type: 'idle' });
    try {
      await action();
      setToast({ type: 'success', message: successMessage });
      await loadAll();
    } catch (err) {
      if ((err as DOMException)?.name === 'AbortError') return;
      setToast({
        type: 'error',
        message: err instanceof ApiError? err.message : 'Something went wrong.',
      });
    } finally {
      setBusy(false);
    }
  }, [loadAll]);

  const restock = useCallback(
    (sku: ProductSku, quantity: number) =>
      runAction(() => serviceApi.restock(sku, quantity), `Added ${quantity} unit(s) to ${sku}.`),
    [runAction],
  );

  const updatePrice = useCallback(
    (sku: ProductSku, price: number) =>
      runAction(() => serviceApi.updatePrice(sku, price), `Updated ${sku} price to ${price.toFixed(2)}€.`),
    [runAction],
  );

  const setChangeInventory = useCallback(
    (counts: Record<number, number>) =>
      runAction(() => serviceApi.setChangeInventory(counts), 'Change inventory saved.'),
    [runAction],
  );

  return { state, transactions, loading, busy, toast, restock, updatePrice, setChangeInventory };
}
