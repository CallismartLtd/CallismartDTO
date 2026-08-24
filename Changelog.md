# Changelog

All notable changes to `Callismart\DTO\DTO` are documented in this file.

## [1.0.0]

### Added
- `required_keys()` extension hook — subclasses declare which keys must be present for the DTO to be considered valid. Defaults to an empty array (nothing required), mirroring the existing `allowed_keys()` / `sensitive_keys()` pattern.
- `missing_keys(): array` — returns the subset of `required_keys()` not currently set. Uses `has()` semantics, so a key explicitly set to `null` counts as present.
- `is_valid(): bool` — `true` when `missing_keys()` is empty.
- `validate(): static` — throws `InvalidArgumentException` (naming the missing keys) if any required key is absent; returns `$this` otherwise for chaining.

### Changed
- `dump()` now also reports `required_keys` and `missing_keys` alongside the existing `allowed_keys`, `class`, `count`, and `props`.
- Class docblock's "Subclasses can override" list and example now include `required_keys()`.

### Design notes
- Required-key validation is **opt-in, not automatic**. `assign()` (the funnel for every write path — magic setters, `offsetSet()`, `fill()`, `merge()`) does not call `validate()`. The DTO is designed to support incremental construction (e.g. building up a `UserDTO` field by field), so enforcing required keys on every write would break that pattern. Call `->validate()` explicitly once construction is complete (e.g. at the end of a subclass constructor, or after `fill()`/`from_json()`).
- No breaking changes — all new members are additive; existing public API and behavior are unchanged.