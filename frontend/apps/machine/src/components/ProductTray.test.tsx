import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import { ProductTray } from './ProductTray';

describe('ProductTray', () => {
  it('renders the emoji hidden from assistive tech', () => {
    const { container } = render(<ProductTray sku="SODA" name="Soda" />);
    const emoji = container.querySelector('[aria-hidden="true"]');
    expect(emoji).toHaveTextContent('🥫');
  });

  it('exposes the dispensed product name only to screen readers', () => {
    render(<ProductTray sku="WATER" name="Water" />);
    expect(screen.getByText('Dispensed: Water')).toHaveClass('sr-only');
  });
});
