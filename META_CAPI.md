# Platform Purchase CAPI

This implementation sends only successfully placed website retail orders from
`OrderController::store`. Payment can still be pending (COD or manual payment).
It does not send vendor CAPI, POS, wholesale, invoice reorders, admin orders,
ViewContent, AddToCart, or InitiateCheckout. Existing browser Pixel events remain.

## Configuration and activation

Defaults are OFF. Set server environment variables only:

- `META_CAPI_ENABLED=false` until verification and operations are ready.
- `META_CAPI_ACCESS_TOKEN`: secret; never commit, expose in a view, or log it.
- `META_GRAPH_VERSION=v26.0`: explicit version, based on
  [Meta's SDK configuration](https://github.com/facebook/facebook-php-business-sdk/blob/main/src/FacebookAds/ApiConfig.php).
- `META_CAPI_QUEUE_CONNECTION=database`: persistent asynchronous queue.

Platform Pixel IDs and scope use the existing marketing settings. Each configured
platform destination must be authorized by the token. The existing platform
`test_event_code` is snapshotted at recording and sent alongside `data`, not inside
an event. Set it for verification, then clear it before recording production events.
Already-recorded test events keep their original test code. A single test code is
shared by the configured platform destinations; verify one destination at a time
if their testing codes differ.

Apply the new additive migration through the normal deployment process. No existing
migration changes. Tests migrate only an asserted in-memory SQLite database.
Do not activate until the platform's consent/privacy policy permits this use of
customer data. This change does not create a new consent UI or reinterpret the
existing `accepts_marketing` preference as a CAPI consent signal.

## Worker and recovery

An example production worker command (not run by this change):

```sh
php artisan queue:work database --queue=meta-capi --sleep=3 --tries=5 --timeout=30
```

Supervise it with the host's process manager; restart workers on deployments.
The connection's `retry_after` must exceed the 30-second job timeout (the existing
database default is 90). HTTP connect/request limits are 3/10 seconds, and the
database claim lease is 60 seconds. Do not run a synchronous analytics queue.

Implementation readiness is separate from production activation: nothing in this
change starts a worker, installs a cron entry, or enables CAPI.

Run Laravel's scheduler using ONE of these alternatives:

```cron
* * * * * cd /absolute/path/to/moslamart && php artisan schedule:run >> /dev/null 2>&1
```

Or supervise `php artisan schedule:work` as a persistent process. Replace the
example project path and use the production PHP executable. Do not run both.

The registered `meta:recover` command runs every five minutes, dispatching up to
100 due records per run. It also supports manual invocation. It recovers both
committed records whose initial dispatch failed and expired worker leases.
Duplicate queue messages cannot claim an already-sent or currently leased record.
Dispatch has a separate atomic ten-minute reservation: repeated/concurrent recovery
runs skip already-queued records, and interrupted dispatch reservations expire.
Queue releases reserve until the retry due time plus ten minutes. Recovery marks
events older than 24 hours failed even when workers are stopped, bounding redelivery
of queue messages. Delivery claims and immutable event identity still guard sends.

Network errors, 408, 429, 5xx, and explicitly transient non-auth API failures retry
after 60, 300, 900, and 3600 seconds. Other 4xx failures stop. Authentication errors
stop even if a transient flag is present. Invalid successful responses retry.
The persistent attempt limit is five, even across recovery/re-dispatch. Events
older than 24 hours stop rather than being replayed with a fabricated event time.
Disabled/unconfigured delivery leaves pending records untouched; old records
expire when delivery resumes. A removed Pixel or changed platform scope cancels
the original snapshot instead of sending it under a new policy.

Monitor pending age, failed/cancelled records, and Laravel `failed_jobs`. Permanent
failures are recorded in the conversion table rather than thrown with remote
response bodies. Fix configuration before a deliberate operator-reviewed replay;
this phase includes no general-purpose replay UI. Keep the same event identity.

## Identity, data, and limitations

One row per `(pixel_id, event_name, event_id)` is enforced by the database.
Purchase uses the order number in both browser `eventID` and CAPI `event_id`.
Original time and commerce/user-data snapshots never change on retries. Delivery
is at least once: a timeout may follow successful remote acceptance. Meta receives
the same identity on a retry; no promise of exactly-once network delivery is made.

Snapshots are Laravel-encrypted; preserve the application encryption key during
deployments/rotation. Queue jobs contain only the record ID plus queue metadata.
Identity fields are normalized and SHA-256 hashed before persistence. IP, user
agent and validated fbp/fbc remain unhashed inside the encrypted snapshot. Full
names are not split heuristically. No delivery address, order note, payment claim,
private URL token, or access token is stored in the event. Logs contain only fixed
messages and record IDs; remote error bodies are discarded. This does not alter
existing application logging outside CAPI.

Set an operational retention policy before activation. Purge encrypted payloads
after the required delivery/audit period while preserving identity tombstones if
replay remains possible. No automatic destructive pruning is included in this phase.
Verify trusted proxy/ingress configuration before relying on captured client IPs.

Platform-all value equals the persisted order grand total. Platform-own uses only
persisted admin-owned items, matching Phase A's existing parameter builder. Legacy
fixed-combo order lines omit vendor ownership: platform-own CAPI conservatively
omits the whole combo event. Platform-all retail combos remain supported. No combo
pricing, vendor fulfillment, or browser behavior is changed. Later expansion to
wholesale/POS must handle their different quantity semantics explicitly.

Analytics-specific recording/logging errors do not block the order; a failed recording itself has
no durable event to recover and emits a sanitized warning. Queue dispatch errors
after a successful recording leave a recoverable row. Duplicate order submissions
that create different order numbers remain different conversions; CAPI idempotency
is not order-submission idempotency.
An actual database transaction loss/deadlock cannot safely be ignored: checkout
returns a sanitized retry error and does not pretend a rolled-back order succeeded.
Savepoints isolate ordinary analytics failures, not MySQL-wide transaction aborts.

The browser still reads the order and marketing settings on the success-page GET.
CAPI preserves the creation snapshot. An intervening admin value/scope/Pixel change
can therefore change the browser event; this pre-existing timing model is not
rewritten here. Own-scope combos have browser-only coverage, not two conflicting
CAPI/browser values. Even currently admin-only combos are conservatively omitted
because their persisted lines do not carry a reliable ownership snapshot.

## Verification

Run the targeted Meta tests, then the complete Laravel suite. No browser asset
build is needed unless frontend assets change.

In Meta Test Events, place one website order and verify browser/server Purchase
have identical names, IDs, currency, value and content IDs; confirm deduplication.
Refresh the success page and verify no extra server record. Exercise COD, manual
payment, platform-own mixed carts, queue interruption/recovery, and ad-blocked
browser delivery. Clear the test code and confirm worker/scheduler monitoring
before enabling production traffic. No live API call is part of automated tests.
