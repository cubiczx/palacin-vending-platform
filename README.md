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

### Seed initial data

After starting the backend for the first time, seed the default machine (3 products, plentiful change):

​```bash
  php bin/console app:seed-machine
​```

This command is idempotent — running it again when the machine already exists is a safe no-op.

## Trade-offs / What I'd improve with more time

- **Slot capacity (max units per slot) was considered but intentionally left out**: the challenge only requires
  tracking current stock count, not a physical maximum per slot position. Adding it would mean extending `Product`
  and validating it on restock without the challenge exercising that rule anywhere — scope not justified here.

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

  - **No dedicated tests for DTOs (Commands, Queries, ReadModel views)**: these are plain `readonly` data
  carriers with no behaviour of their own — constructor assignment is guaranteed by PHP, so testing them in
  isolation would only pin down language semantics, not business rules. Their correctness is exercised
  indirectly through the handler tests that construct and consume them (e.g. `GetMachineStateQueryHandlerTest`
  verifies `ProductView` never leaks exact stock counts).

- **Optimistic locking via `#[ODM\Version]`**: relies on Doctrine ODM's per-request identity map — within a single
  request, the same managed document instance flows from `find()` through to `save()`, preserving the version it
  was loaded with, so a stale write from a concurrent request correctly triggers a `LockException` at `flush()`.
  This is verified in `VendingMachineRepositoryTest::testConcurrentSavesDetectVersionConflictViaOptimisticLocking`
  (using `DocumentManager::clear()` between reads to simulate two independent requests). Not yet handled: a
  `LockException` currently surfaces as a generic 500 rather than a clean HTTP conflict response.
