import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import { Toast } from './Toast';

describe('Toast', () => {
  it('renders nothing when idle', () => {
    const { container } = render(<Toast toast={{ type: 'idle' }} />);
    expect(container).toBeEmptyDOMElement();
  });

  it('renders a status role with the message on success', () => {
    render(<Toast toast={{ type: 'success', message: 'Change inventory saved.' }} />);
    expect(screen.getByRole('status')).toHaveTextContent('Change inventory saved.');
  });

  it('renders an alert role with the message on error', () => {
    render(<Toast toast={{ type: 'error', message: 'Something went wrong.' }} />);
    expect(screen.getByRole('alert')).toHaveTextContent('Something went wrong.');
  });
});
