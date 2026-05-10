# darejer benchmark baseline + improvement plan

Baseline captured by `tests/Feature/Benchmarks/*` on the in-memory SQLite
test driver (`vendor/bin/pest tests/Feature/Benchmarks --compact`).

Wall times are *relative*; query counts are the load-bearing signal.

## Baseline (2026-05-10)

| Subject | Time | Queries | Memory |
| --- | ---: | ---: | ---: |
| `DataTable::render` (500 rows, default page) | 11 ms | 2 | 0.5 MB |
| `DataTable::render` (search) | 4 ms | 2 | 0.1 MB |
| `DataTable::render` (select filter) | 4 ms | 2 | 0.1 MB |
| `DataTable::render` (date range) | 3 ms | 2 | 0.1 MB |
| `DataTable::render` (sort=-price) | 3 ms | 2 | 0.1 MB |
| `DataTable::render` (per_page=100) | 2 ms | 2 | 0.1 MB |
| `GlobalSearch::search` (3 models, match) | 5 ms | 3 | 0.1 MB |
| `GlobalSearch::search` (3 models, no match) | 2 ms | 3 | 0.1 MB |
| `GlobalSearch::search` (per_model=20) | 4 ms | 3 | 0.1 MB |
| `DataController::index` (combobox autocomplete, 1k rows) | 69 ms | 2 | 6.0 MB |
| `DataController::index` (per_page=50) | 3 ms | 2 | 0.1 MB |
| **`DataController::index` (tree, all rows)** | **105 ms** | **1** | **1.6 MB** |

## Findings

### 0. `AuditWriter::buffer()` — new opt-in helper (added 2026-05-10)

`Darejer\Support\AuditWriter` now exposes `buffer(callable): mixed`. Inside
the callable every call to `AuditWriter::write()` appends to an in-memory
array; on success the rows flush in a single bulk INSERT into
`audit_logs`, on exception the buffer is discarded, and nested
`buffer()` calls passthrough so the outer scope owns the flush.

Used by `syntax-crm`'s `PosCheckoutService::handle` to collapse 22
per-save `audit_logs` inserts into one. Other hot paths that touch
many auditable models (batch postings, importers) can opt in the same
way. See `tests/Unit/AuditableTest.php` for the contract.

### Solid — keep it that way

- **DataTable** rendering is at the floor (2 queries: count + page). Filters
  and sort don't add queries. **No fix needed**.
- **GlobalSearch** runs exactly 1 query per registered searchable model.
  No N+1 in the per-row label/subtitle resolution (those use already-loaded
  attributes, not lazy relations).
- **DataController** combobox + paginated-page paths both stay at 2
  queries. Good.

### 1. `GlobalSearch::search` is O(N) in registered models — debounce on the client matters

Today: 3 models = 3 queries. syntax-crm registers many more models (Customer,
Supplier, Item, SalesInvoice, PurchaseInvoice, ChartOfAccount, Branch, …).
Each keystroke from the topbar runs one LIKE query per registered
searchable model, in series.

**Why it's not on the urgent list:**
- The trait already uses indexed `LIKE 'term%'` (prefix) where it can.
- Frontend already debounces topbar input.
- 60 hits/min/user rate limiter caps damage.

**When it'll bite:**
- ~20+ registered models *and* a slow tail (one model with a big table
  and a non-indexed search column). Watch for the slowest query in the
  next baseline run with all syntax-crm models registered.

**Fixes, in order of effort:**

1. **Per-model `searchableTerm` scope must use indexed columns.** Audit
   every model that uses `Searchable` and make sure the columns in
   `protected array $searchable` are covered by an index (composite
   `(company_id, code)`, etc.). One slow column drags the whole topbar.
2. **Cap concurrent work.** If a slow model exists, run it through the
   queue or skip it for terms < 3 chars. The current trait already skips
   empty terms.
3. **Async fan-out** is overkill for now; ignore unless query count
   becomes a real complaint.

Add a focused benchmark (`tests/Feature/Benchmarks/GlobalSearchScaleBenchmarkTest.php`)
that registers 20+ models and re-asserts the ceilings.

### 2. `DataController::index` tree mode loads every row into memory

`DataController.php:63-72` — when `?tree=1` is passed, the controller
calls `$query->get()` (no pagination) and builds the nested tree in PHP.
Today: 105 ms / 1.6 MB for 1 000 rows.

**Why it matters:**
- Used by the Combobox / TreeGrid components for parent-child pickers
  (chart of accounts, departments, item categories).
- A 50 000-row chart of accounts would be 50× the memory and time, on
  every page that mounts the picker.

**Fixes, in order of effort:**

1. **Cap unbounded tree mode.** Hard-cap at e.g. 5 000 rows; if the
   table exceeds that, return a flat paginated list and let the picker
   open lazily.
2. **Cache the tree per (model, locale, filters)** under
   `darejer:tree:<model>:<hash>` with a short TTL. The hierarchy
   changes rarely; rebuilding it on every page-load is wasteful. Models
   with `darejerCacheTtl()` already get this for paginated mode — do
   the same for tree mode.
3. **`buildTree()` is currently O(N²)** (line 67 inside `paginateResponse`'s
   sibling). Verify it builds a parent map first; if it nests by
   re-walking, swap to a single-pass index.

### 3. `DataController` combobox uses 6 MB on first call — first-request warm-up

The combobox autocomplete (1k rows, q=City 5, per_page=20) reports 6 MB
peak memory. That's PHP container/autoload warm-up for the first request
in the test (subsequent requests in the same suite drop to ~96 KB). It is
**not a real bug**, but flag it: if the test process is memory-tight (CI
with a small worker), DataController being the first hit could spike
peak. Worth retrying with the test ordered differently to confirm.

## What's NOT covered yet

These were considered and skipped — easy follow-ups if the surface
matters:

- **Form rendering** — `Form::make(...)->renderAsScreen()` builds a big
  payload for Inertia. No DB queries in the render itself, but the
  serialized JSON size affects TTFB.
- **EditableTable** — bulk-row form. Saves go through a single endpoint
  per submit. Likely fine; benchmark when it appears in a hot flow.
- **Kanban / TreeGrid** — niche; benchmark on demand.
- **Translatable rule** — runs per-field on form save. Cheap on its own;
  could pile up on long forms. Add a micro-benchmark if a slow form
  appears.

## How to run

```bash
vendor/bin/pest tests/Feature/Benchmarks --compact
vendor/bin/pest tests/Feature/Benchmarks/DataTableBenchmarkTest.php --compact
```

## How to add a new benchmark

```php
use Darejer\Tests\Support\Benchmark;

it('benchmarks something', function () {
    // Schema::create / seed rows / register models in beforeEach()

    $result = Benchmark::run('my.subject', fn () => doTheThing())
        ->report();

    $result->assertQueriesAtMost(5)->assertFasterThan(300);
});
```
