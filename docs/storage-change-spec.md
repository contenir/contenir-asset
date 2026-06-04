# contenir/storage — Change Spec for contenir-asset 2.0.0

**Status:** Phase 0 design
**Date:** 2026-06-04
**Why:** `contenir-asset` 2.0.0 is a thin layer over `contenir/storage`. Several capabilities it depends on do **not exist yet** in storage. This spec is the precedent storage release; asset 2.0.0 `require`s the exact minor that ships it (`contenir/storage:^X.Y`, `conflict` older), and a CI matrix verifies the boundary.

Grounded in the current source: `ImageResizer.php` (shells out, no guards), `StorageInterface.php` (no namespace/Location surface), `S3.php` (`knownKeys` cache, silent catches, SVG accept), `InMemoryStorage.php` (no-op regenerate).

---

## 1. Per-asset namespace + Location surface *(enables safe GC)*

Today variants are **siblings in a shared directory** (`hero__card.avif` next to `hero.jpg`), so "list the namespace, diff, prune" is unsafe — a directory listing returns unrelated assets. Add an **exclusive per-asset namespace** layout (`asset/<id>/…`) as a per-profile option (new profiles default on; legacy profiles keep siblings until a site cuts over), and a backend-neutral addressing surface:

```
interface StorageInterface  // additions
  address(string $namespace, string $variant, string $format): Location
  list(string $namespace): Location[]          // everything under one asset's namespace
  delete(Location $location): void
  deleteMany(Location[] $locations): void
  url(Location $location): string              // pure: no existence check, no throw, no network
```

- `Location` is a value object (backend-neutral address). For S3/R2 it wraps a key; for FS a path; for Cloudflare image-id × transform.
- `url(Location)` must be **pure** — build the URL from the address only. The current `url(path, variant)` returns `null`/throws on a missing/unknown variant; that behaviour must NOT be on this method (it would break asset's no-throw/no-network read invariant).
- Namespace exclusivity is the precondition asset asserts before pruning (the original must appear in its own namespace listing).

## 2. Dimension capture from generation *(the core enabler)*

`ImageResizer::resize()` returns `void` and `regenerateMissingVariants()` returns only `list<string>` of keys. Asset's headline value (per-rendition real dims → CLS fix + real `srcset`) is **unimplementable** against this. Change generation to return per-rendition output metadata:

```
final readonly class ImageMeta {
  public string $key;
  public string $format;
  public string $mime;
  public int $width;        // ACTUAL output dimensions (contain may not fill its box)
  public int $height;
  public int $filesize;
  public string $hash;      // content hash of the output bytes (versioned-key token + write validator)
}

// resize(...) and regenerateMissingVariants(...) return ImageMeta per rendition produced.
```

Capture dims from the resizer's own output (Imagick knows them; no re-read). Do **not** make asset re-`HEAD`/`getimagesize` written objects.

## 3. Write-response checksum *(integrity without an extra round-trip)*

Expose the strong validator (ETag/checksum) returned by the object PUT, compared to the locally computed `hash`. Asset verifies the byte landed intact via this — **not** a separate HEAD (HEAD-200 doesn't prove byte integrity and costs a round-trip). Surface it on the write result / `ImageMeta`.

## 4. Versioned-key tokens *(immutable caching)*

Adopt a token slot in the variant key (`…/card.<hash>.<format>`) so every regeneration is a new immutable object servable `Cache-Control: immutable, max-age=1y`. Token = content `hash` from §2. The current tokenless `__variant.ext` scheme stays for legacy profiles. This touches `variantKey()` and the delete/rename sibling logic.

## 5. Ingest guards in the resizer *(security + correctness)*

Replace `exec('… 2>/dev/null')` (shell string, no limits, stderr discarded):

- **No shell:** Imagick extension when loaded (in-process `setResourceLimit`), else `proc_open` with an **argv array** and a configured absolute binary path (never `which` at runtime in prod).
- **Limits:** ship a `policy.xml` (memory/map/disk/width/height/area/time); reject originals above a dimension/area cap via a header probe *before* full decode; hard wall-clock timeout.
- **Correctness:** `-auto-orient` (fixes EXIF dimension swap → the CLS bug), `-strip`, `-colorspace sRGB`.
- **Non-raster:** raster MIME allowlist; SVG/PDF/video → pass-through (store original, no variants). **SVG** is sanitised (`enshrined/svg-sanitize`) or flagged for safe serving — it carries XSS/XXE/SSRF in its bytes independent of ImageMagick.
- **Capture stderr** on failure for the observability event (§7).

## 6. Test seams: clock, lock, verify *(testability of the safety-critical paths)*

Add injectable seams so asset's destructive paths are unit-testable (and to satisfy the no-ambient-`time()` rule):

- `ClockInterface` (PSR-20) — no bare `new DateTimeImmutable()`.
- `LockInterface` — `acquire(key, ttl)` / `release`, short-timeout + bounded retry semantics (per-asset advisory lock).
- `VerifyInterface` — assert a written object's checksum/size matches.

Provide in-memory fakes in `tests/TestAsset`.

## 7. Observability seams *(the alarm must be wired to something)*

Inject PSR-3 logger + a metrics sink (counter interface) into the resizer, S3 adapter, and any reconcile primitive. Emit structured `variant.generated` / `variant.generate_failed{stderr}` / delete events. Stop swallowing `FilesystemException`s silently in `delete()`/`rename()`/`buildEntry()`.

## 8. Test-double parity *(so asset can test against storage in-memory)*

- `InMemoryStorage::regenerateMissingVariants()` must actually materialise synthetic renditions (deterministic bytes + injected dims) and support the new `list(namespace)`/`address`, so asset's `generate → rewrite → prune` ordering is unit-testable without S3.
- Reconcile must read the present-set from an authoritative `list(namespace)`, and call `clearKeyCache()` per asset — the `S3::$knownKeys` positive cache is a read-path optimisation only, never a GC source of truth.

---

## Acceptance (storage release is "done for asset" when)

- [ ] Per-asset namespace layout (opt-in per profile) + `address`/`list`/`delete(Location)`/`deleteMany`/pure `url(Location)`.
- [ ] `resize`/`regenerate*` return `ImageMeta[]` with actual dims + content hash.
- [ ] Write-response checksum exposed.
- [ ] Versioned-key token support (new profiles), legacy scheme preserved.
- [ ] Ingest guards: no-shell, policy.xml, caps, timeout, auto-orient, strip, sRGB; SVG handling decided.
- [ ] Clock/Lock/Verify interfaces + in-memory fakes.
- [ ] PSR-3 + metrics seams; no silent catches; captured stderr.
- [ ] `InMemoryStorage` materialises variants + supports namespace; reconcile bypasses `knownKeys` for GC.
- [ ] Tagged minor; asset 2.0.0 pins it; CI `--prefer-lowest`/highest matrix green.
