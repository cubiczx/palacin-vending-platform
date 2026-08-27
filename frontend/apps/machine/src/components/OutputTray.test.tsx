import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import { OutputTray } from './OutputTray';

describe('OutputTray', () => {
  it('shows the empty state by default', () => {
    render(<OutputTray content={{ type: 'empty' }} />);
    expect(screen.getByText('— Empty —')).toBeInTheDocument();
  });

  it('shows the dispensed product and its change on a vended result', () => {
    render(
      <OutputTray
        content={{
          type: 'vended',
          product: 'WATER',
          productName: 'Water',
          change: { '0.25': 1, '0.10': 1 },
        }}
      />,
    );
    expect(screen.getByText('Dispensed: Water')).toBeInTheDocument();
    expect(screen.getByText('Change')).toBeInTheDocument();
    expect(screen.getByText('0.25')).toBeInTheDocument();
    expect(screen.getByText('0.10')).toBeInTheDocument();
  });

  it('shows returned coins on a returned result', () => {
    render(<OutputTray content={{ type: 'returned', coins: { '0.10': 2 } }} />);
    expect(screen.getByText('Returned')).toBeInTheDocument();
    expect(screen.getAllByText('0.10')).toHaveLength(2);
  });

  it('shows the error message and uses an alert-friendly live region', () => {
    render(<OutputTray content={{ type: 'error', message: 'Insufficient funds.' }} />);
    expect(screen.getByRole('status')).toHaveTextContent('Insufficient funds.');
  });
});
