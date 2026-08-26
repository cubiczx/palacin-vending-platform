import type { Transaction } from '../types/service';
import { PRODUCT_VISUALS } from '../types/service';

export function TransactionHistoryTable({ transactions }: { transactions: Transaction[] }) {
  if (transactions.length === 0) {
    return (
      <div className="rounded-2xl border border-slate-200 bg-white p-5 text-sm text-slate-500 shadow-sm">
        No sales recorded yet.
      </div>
    );
  }

  return (
    <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
      <table className="w-full text-left text-sm">
        <caption className="sr-only">Recent transactions</caption>
        <thead className="bg-slate-50 text-xs tracking-wide text-slate-500 uppercase">
          <tr>
            <th scope="col" className="px-4 py-2">Product</th>
            <th scope="col" className="px-4 py-2">Price</th>
            <th scope="col" className="px-4 py-2">When</th>
          </tr>
        </thead>
        <tbody>
          {transactions.map((tx) => (
            <tr key={tx.id ?? tx.occurredAt} className="border-t border-slate-100">
              <td className="px-4 py-2">
                <span aria-hidden="true" className="mr-1">{PRODUCT_VISUALS[tx.product].emoji}</span>
                {tx.product}
              </td>
              <td className="px-4 py-2">{tx.price.toFixed(2)}€</td>
              <td className="px-4 py-2 text-slate-500">{new Date(tx.occurredAt).toLocaleString()}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
