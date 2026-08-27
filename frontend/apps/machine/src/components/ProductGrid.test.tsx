import { describe, it, expect, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { ProductGrid } from './ProductGrid';
import type { Product } from '../types/machine';

const products: Product[] = [
  { sku: 'WATER', name: 'Water', price: 0.65, inStock: true },
  { sku: 'JUICE', name: 'Juice', price: 1.0, inStock: false },
];

describe('ProductGrid', () => {
  it('renders every product with its price', () => {
    render(<ProductGrid products={products} balance={0} busy={false} onSelect={vi.fn()} />);
    expect(screen.getByText('Water')).toBeInTheDocument();
    expect(screen.getByText('0.65€')).toBeInTheDocument();
    expect(screen.getByText('Juice')).toBeInTheDocument();
  });

  it('shows a "Sold out" badge and disables Buy for out-of-stock products', () => {
    render(<ProductGrid products={products} balance={10} busy={false} onSelect={vi.fn()} />);
    expect(screen.getByText('Sold out')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /buy juice.*out of stock/i })).toBeDisabled();
  });

  it('shows how much more is needed when the balance is insufficient', () => {
    render(<ProductGrid products={products} balance={0.1} busy={false} onSelect={vi.fn()} />);
    expect(screen.getByText('Insert 0.55€ more')).toBeInTheDocument();
  });

  it('does not show the insert-more hint once affordable', () => {
    render(<ProductGrid products={products} balance={0.65} busy={false} onSelect={vi.fn()} />);
    expect(screen.queryByText(/insert .* more/i)).not.toBeInTheDocument();
  });

  it('calls onSelect with the sku when Buy is clicked', async () => {
    const onSelect = vi.fn();
    const user = userEvent.setup();
    render(<ProductGrid products={products} balance={1} busy={false} onSelect={onSelect} />);

    await user.click(screen.getByRole('button', { name: /buy water/i }));

    expect(onSelect).toHaveBeenCalledWith('WATER');
  });

  it('disables every Buy button while busy, even for in-stock products', () => {
    render(<ProductGrid products={products} balance={1} busy={true} onSelect={vi.fn()} />);
    expect(screen.getByRole('button', { name: /buy water/i })).toBeDisabled();
  });
});
