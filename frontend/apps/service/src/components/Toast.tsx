type ToastState = { type: 'idle' } | { type: 'success'; message: string } | { type: 'error'; message: string };

export function Toast({ toast }: { toast: ToastState }) {
  if (toast.type === 'idle') return null;

  return (
    <div
      role={toast.type === 'error' ? 'alert' : 'status'}
      aria-live="polite"
      className={`rounded-xl px-4 py-2 text-sm font-medium ${
        toast.type === 'error' ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700'
      }`}
    >
      {toast.message}
    </div>
  );
}
