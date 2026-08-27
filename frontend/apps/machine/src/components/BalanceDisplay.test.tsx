import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import { BalanceDisplay } from './BalanceDisplay';

describe('BalanceDisplay', () => {
  it('formats the amount with two decimals and the euro sign', () => {
    render(<BalanceDisplay amount={1.5} />);
    expect(screen.getByText('1.50€')).toBeInTheDocument();
  });

  it('formats zero balance correctly', () => {
    render(<BalanceDisplay amount={0} />);
    expect(screen.getByText('0.00€')).toBeInTheDocument();
  });

  it('exposes the balance as a polite live region for screen readers', () => {
    render(<BalanceDisplay amount={0.65} />);
    expect(screen.getByText('0.65€')).toHaveAttribute('aria-live', 'polite');
  });
});
