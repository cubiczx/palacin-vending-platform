import { describe, it, expect, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { CoinInsertionPanel } from './CoinInsertionPanel';

describe('CoinInsertionPanel', () => {
  it('renders a button for every accepted denomination', () => {
    render(<CoinInsertionPanel onInsert={vi.fn()} disabled={false} />);
    expect(screen.getByRole('button', { name: /insert 0\.05 euros/i })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /insert 0\.10 euros/i })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /insert 0\.25 euros/i })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /insert 1\.00 euros/i })).toBeInTheDocument();
  });

  it('calls onInsert with the coin value in cents when clicked', async () => {
    const onInsert = vi.fn();
    const user = userEvent.setup();
    render(<CoinInsertionPanel onInsert={onInsert} disabled={false} />);

    await user.click(screen.getByRole('button', { name: /insert 0\.25 euros/i }));

    expect(onInsert).toHaveBeenCalledWith(25);
  });

  it('disables the whole fieldset when disabled is true', () => {
    render(<CoinInsertionPanel onInsert={vi.fn()} disabled={true} />);
    expect(screen.getByRole('group')).toBeDisabled();
  });

  it('is keyboard accessible via Enter', async () => {
    const onInsert = vi.fn();
    const user = userEvent.setup();
    render(<CoinInsertionPanel onInsert={onInsert} disabled={false} />);

    await user.tab();
    await user.keyboard('{Enter}');

    expect(onInsert).toHaveBeenCalledWith(5);
  });
});
