import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import { CoinTray } from './CoinTray';

describe('CoinTray', () => {
  it('shows the empty label when there are no coins', () => {
    render(<CoinTray coins={{}} label="Change" emptyLabel="No change due" />);
    expect(screen.getByText('No change due')).toBeInTheDocument();
  });

  it('ignores zero-quantity denominations', () => {
    render(<CoinTray coins={{ '0.25': 0, '0.10': 2 }} label="Change" emptyLabel="No change due" />);
    expect(screen.queryByText('No change due')).not.toBeInTheDocument();
    expect(screen.getAllByText('0.10')).toHaveLength(2);
  });

  it('renders one chip per coin unit, expanding quantities', () => {
    render(<CoinTray coins={{ '0.25': 1, '0.10': 3 }} label="Returned" emptyLabel="Nothing" />);
    expect(screen.getAllByText('0.25')).toHaveLength(1);
    expect(screen.getAllByText('0.10')).toHaveLength(3);
  });

  it('shows the given label', () => {
    render(<CoinTray coins={{ '1.00': 1 }} label="Returned" emptyLabel="Nothing" />);
    expect(screen.getByText('Returned')).toBeInTheDocument();
  });
});
