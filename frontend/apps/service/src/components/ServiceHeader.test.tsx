import { describe, it, expect, afterEach, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import { ServiceHeader } from './ServiceHeader';

describe('ServiceHeader', () => {
  afterEach(() => {
    vi.unstubAllEnvs();
  });

  it('renders the brand and mode label', () => {
    render(<ServiceHeader />);
    expect(screen.getByText('PALACÍN')).toBeInTheDocument();
    expect(screen.getByText('Service Mode')).toBeInTheDocument();
  });

  it('links back to the customer view with a default URL when the env var is unset', () => {
    render(<ServiceHeader />);
    expect(screen.getByRole('link', { name: /customer view/i })).toHaveAttribute(
      'href',
      'http://localhost:5173',
    );
  });

  it('uses VITE_MACHINE_APP_URL when provided', () => {
    vi.stubEnv('VITE_MACHINE_APP_URL', 'https://machine.example.com');
    render(<ServiceHeader />);
    expect(screen.getByRole('link', { name: /customer view/i })).toHaveAttribute(
      'href',
      'https://machine.example.com',
    );
  });
});
