import { describe, it, expect, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { ReturnCoinsButton } from './ReturnCoinsButton';

describe('ReturnCoinsButton', () => {
  it('labels the button with the exact amount when enabled', () => {
    render(<ReturnCoinsButton disabled={false} amount={1.35} onClick={vi.fn()} />);
    expect(screen.getByRole('button', { name: 'Return 1.35 euros' })).toBeInTheDocument();
  });

  it('falls back to a generic label when disabled', () => {
    render(<ReturnCoinsButton disabled={true} amount={0} onClick={vi.fn()} />);
    expect(screen.getByRole('button', { name: 'Return coins' })).toBeDisabled();
  });

  it('calls onClick when pressed', async () => {
    const onClick = vi.fn();
    const user = userEvent.setup();
    render(<ReturnCoinsButton disabled={false} amount={0.5} onClick={onClick} />);

    await user.click(screen.getByRole('button'));

    expect(onClick).toHaveBeenCalledOnce();
  });

  it('does not call onClick when disabled', async () => {
    const onClick = vi.fn();
    const user = userEvent.setup();
    render(<ReturnCoinsButton disabled={true} amount={0} onClick={onClick} />);

    await user.click(screen.getByRole('button'));

    expect(onClick).not.toHaveBeenCalled();
  });
});
