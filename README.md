# palacin-vending-platform
🎓 Vending Machine Technical Challenge — Full-stack implementation with Symfony & React.

## Development environment

This project was developed and tested locally using [Laravel Herd](https://herd.laravel.com/) (PHP/Symfony) and a
native MongoDB installation on Windows — not Docker.

A `Dockerfile` and `docker-compose.yml` are included to make evaluation easier with a single command, as requested
in the challenge. However, due to virtualization limitations on the development machine, **the Docker setup could
not be tested end-to-end locally**. The configuration follows standard practices for this stack (PHP-FPM + Nginx,
Node build + Nginx static serving, MongoDB container) and should work as-is, but please report any issues if you
run into them when evaluating.

## Trade-offs / What I'd improve with more time

- **Transaction history date filtering**: `GET /api/service/transactions` accepts a `product` filter, but `from`/`to`
  query params for date-range filtering are not yet parsed at the HTTP boundary, even though the underlying query
  and repository already support it. Wiring this up is a small, isolated addition to `ServiceController::transactions()`.

- **Change-making algorithm**: `ChangeCalculator` uses a greedy algorithm (always take the largest denomination that
  fits), which is optimal for this canonical denomination set (5, 10, 25, 100 cents) under unlimited supply. Because
  the machine's coin supply is limited, the greedy result is validated against the available inventory; if it can't
  be satisfied exactly, the purchase is rejected with an `ExactChangeUnavailableException` rather than shortchanging
  the customer. A handful of edge cases exist where greedy fails despite a valid combination existing using more
  low-value coins (e.g. plenty of 0.05/0.10 in stock but zero 0.25 for 0.30 due). An exhaustive/backtracking search
  would close this gap; given the small, bounded denomination set, this is an intentional, documented simplification.

- **Change is drawn from the machine's existing inventory, not from the current session's inserted coins**: when a
  purchase completes, change is always calculated against the `ChangeInventory` the machine already had *before* the
  transaction — coins the customer just inserted are deposited into that inventory only *after* change has been
  successfully withdrawn from it. A real machine could sometimes "reuse" a just-inserted coin to complete change for
  the same transaction; modelling that would require solving a more complex bin-packing problem for comparatively
  little benefit in this scope, so it was deliberately left out.
