import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import { TransactionHistoryTable } from './TransactionHistoryTable';
import type { Transaction } from '../types/service';

const transactions: Transaction[] = [
  {
    id: '1',
    product: 'WATER',
    price: 0.65,
    amountInserted: 1,
    changeReturned: { coins: { '0.25': 1, '0.10': 1 } },
    occurredAt: '2026-08-24T10:00:00+00:00',
  },
];

describe('TransactionHistoryTable', () => {
  it('shows an empty state message when there are no transactions', () => {
    render(<TransactionHistoryTable transactions={[]} />);
    expect(screen.getByText('No sales recorded yet.')).toBeInTheDocument();
  });

  it('renders a row per transaction with product and price', () => {
    render(<TransactionHistoryTable transactions={transactions} />);
    expect(screen.getByText('WATER')).toBeInTheDocument();
    expect(screen.getByText('0.65€')).toBeInTheDocument();
  });

  it('renders a semantic table with column headers', () => {
    render(<TransactionHistoryTable transactions={transactions} />);
    expect(screen.getByRole('table')).toBeInTheDocument();
    expect(screen.getByRole('columnheader', { name: 'Product' })).toBeInTheDocument();
    expect(screen.getByRole('columnheader', { name: 'Price' })).toBeInTheDocument();
    expect(screen.getByRole('columnheader', { name: 'When' })).toBeInTheDocument();
  });
});
