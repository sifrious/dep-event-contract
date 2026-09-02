# Event Contract

Framework-neutral PHP values for the `sifrious.cross-package-event` v1 wire
contract. Producers can construct and serialize events without installing
Funes, Laravel, Illuminate, a queue, or a persistence implementation.

Funes remains the canonical historian and acceptance authority. This package
owns only immutable event values, validation, deterministic serialization,
fingerprinting, and event-id idempotency identity.

```bash
composer test
```

## Architecture

`EventEnvelopeContract` is the consumer-facing interface.
`AbstractEventEnvelope` owns provider-neutral validation and serialization.
`EventEnvelope` is the canonical concrete v1 value. Provider packages should
use adapters or factories to create it instead of defining provider-specific
event-envelope subclasses.

Delivery attempts, retries, transports, outboxes, dead letters, authorization,
and history storage are deliberately outside this package.
