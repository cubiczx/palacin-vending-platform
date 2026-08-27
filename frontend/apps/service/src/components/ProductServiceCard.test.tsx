import { describe, it, expect, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { ProductServiceCard } from './ProductServiceCard';
import type { ServiceProduct } from '../types/service';

const product: ServiceProduct = { sku: 'SODA', name: 'Soda', price: 1.5, stock: 3 };

describe('ProductServiceCard', () => {
  it('shows the product name, stock and price', () => {
    render(<ProductServiceCard product={product} busy={false} onRestock={vi.fn()} onUpdatePrice={vi.fn()} />);
    expect(screen.getByText('Soda')).toBeInTheDocument();
    expect(screen.getByText('3')).toBeInTheDocument();
    expect(screen.getByText(/1\.50€/)).toBeInTheDocument();
  });

  it('calls onRestock with the entered quantity on submit', async () => {
    const onRestock = vi.fn();
    const user = userEvent.setup();
    render(<ProductServiceCard product={product} busy={false} onRestock={onRestock} onUpdatePrice={vi.fn()} />);

    const input = screen.getByLabelText('Add units');
    await user.clear(input);
    await user.type(input, '5');
    await user.click(screen.getByRole('button', { name: 'Restock' }));

    expect(onRestock).toHaveBeenCalledWith(5);
  });

  it('does not call onRestock for a zero or invalid quantity', async () => {
    const onRestock = vi.fn();
    const user = userEvent.setup();
    render(<ProductServiceCard product={product} busy={false} onRestock={onRestock} onUpdatePrice={vi.fn()} />);

    const input = screen.getByLabelText('Add units');
    await user.clear(input);
    await user.type(input, '0');
    await user.click(screen.getByRole('button', { name: 'Restock' }));

    expect(onRestock).not.toHaveBeenCalled();
  });

  it('calls onUpdatePrice with the entered price on submit', async () => {
    const onUpdatePrice = vi.fn();
    const user = userEvent.setup();
    render(<ProductServiceCard product={product} busy={false} onRestock={vi.fn()} onUpdatePrice={onUpdatePrice} />);

    const input = screen.getByLabelText('New price (€)');
    await user.clear(input);
    await user.type(input, '1.75');
    await user.click(screen.getByRole('button', { name: 'Update' }));

    expect(onUpdatePrice).toHaveBeenCalledWith(1.75);
  });

  it('pre-fills the price input with the current price', () => {
    render(<ProductServiceCard product={product} busy={false} onRestock={vi.fn()} onUpdatePrice={vi.fn()} />);
    expect(screen.getByLabelText('New price (€)')).toHaveValue(1.5);
  });

  it('shows out-of-stock styling context when stock is zero', () => {
    render(
      <ProductServiceCard
        product={{ ...product, stock: 0 }}
        busy={false}
        onRestock={vi.fn()}
        onUpdatePrice={vi.fn()}
      />,
    );
    expect(screen.getByText('0')).toHaveClass('text-red-600');
  });

  it('disables both forms while busy', () => {
    render(<ProductServiceCard product={product} busy={true} onRestock={vi.fn()} onUpdatePrice={vi.fn()} />);
    expect(screen.getByRole('button', { name: 'Restock' })).toBeDisabled();
    expect(screen.getByRole('button', { name: 'Update' })).toBeDisabled();
    expect(screen.getByLabelText('Add units')).toBeDisabled();
    expect(screen.getByLabelText('New price (€)')).toBeDisabled();
  });
});
