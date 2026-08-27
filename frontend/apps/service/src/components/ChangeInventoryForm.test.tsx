import { describe, it, expect, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { ChangeInventoryForm } from './ChangeInventoryForm';

describe('ChangeInventoryForm', () => {
  it('pre-fills each denomination input from the given coins map', () => {
    render(
      <ChangeInventoryForm
        coins={{ '0.05': 40, '0.10': 30, '0.25': 20, '1.00': 10 }}
        busy={false}
        onSave={vi.fn()}
      />,
    );
    expect(screen.getByLabelText('0.05€')).toHaveValue(40);
    expect(screen.getByLabelText('0.10€')).toHaveValue(30);
    expect(screen.getByLabelText('0.25€')).toHaveValue(20);
    expect(screen.getByLabelText('1€')).toHaveValue(10);
  });

  it('defaults missing denominations to zero', () => {
    render(<ChangeInventoryForm coins={{}} busy={false} onSave={vi.fn()} />);
    expect(screen.getByLabelText('0.05€')).toHaveValue(0);
  });

  it('calls onSave with the full counts map, keyed by cents, on submit', async () => {
    const onSave = vi.fn();
    const user = userEvent.setup();
    render(<ChangeInventoryForm coins={{ '0.05': 40 }} busy={false} onSave={onSave} />);

    await user.click(screen.getByRole('button', { name: 'Save full inventory' }));

    expect(onSave).toHaveBeenCalledWith({ 5: 40, 10: 0, 25: 0, 100: 0 });
  });

  it('reflects edits to an individual denomination before saving', async () => {
    const onSave = vi.fn();
    const user = userEvent.setup();
    render(<ChangeInventoryForm coins={{}} busy={false} onSave={onSave} />);

    const input = screen.getByLabelText('0.25€');
    await user.clear(input);
    await user.type(input, '15');
    await user.click(screen.getByRole('button', { name: 'Save full inventory' }));

    expect(onSave).toHaveBeenCalledWith(expect.objectContaining({ 25: 15 }));
  });

  it('disables inputs and the submit button while busy', () => {
    render(<ChangeInventoryForm coins={{}} busy={true} onSave={vi.fn()} />);
    expect(screen.getByLabelText('0.05€')).toBeDisabled();
    expect(screen.getByRole('button', { name: 'Save full inventory' })).toBeDisabled();
  });
});
