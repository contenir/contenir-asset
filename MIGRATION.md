# Migration Guide

## Upgrading `contenir/contenir-asset` 1.x → 2.0.0

2.0.0 replaces implicit, convention-based variants with an explicit **variant manifest** recorded on each asset row. See `docs/variant-manifest-architecture.md` for the design and `docs/storage-change-spec.md` for the storage prerequisites; this guide is the upgrade runbook.

**The upgrade is incremental, reversible, and non-destructive to live assets.** 2.0.0 ships a **dual-mode** resolver (manifest-first, legacy-fallback) and migrates by **copy-forward into a new bucket**, leaving the old bucket read-only as both the migration source and the rollback. Nothing renders differently the moment you install it; each site commits to the new path only once its backfill is verified.

> **Golden rule:** never enable garbage collection until a site is fully backfilled *and* switched to `manifest-only`. Pruning before that deletes live files. This is the one irreversible action — everything else is a mode flip or a bucket swap.

---

## What breaks (and what doesn't)

| Area | Change | Impact |
|------|--------|--------|
| Helpers | `AssetSrcSet`/`AssetSizes` → `AssetVariants`/`AssetPicture` | Templates keep working in `legacy`/`dual`; update at leisure |
| Controller | `ImageResizeController` superseded | Stays as a `legacy` strategy until cutover |
| Schema | New `variants` JSON + original `width/height/mime/filesize` columns | **Additive, nullable** — no existing query breaks |
| Storage | New bucket per site (namespaced layout); `contenir/storage` ≥ the spec'd minor | Old bucket untouched, read-only |
| Config | A `VariantRegistry` (in storage) is the single source of truth | Must be defined before manifest writing |

In `dual` mode (default), none of this changes runtime rendering — manifests are empty, the legacy strategy drives everything from the old bucket.

---

## Before you begin (prerequisites & mitigations)

1. **Back up the database** (schema change is additive, but still).
2. **Provision a new namespaced bucket** for this site; keep the old bucket **read-only**. Relative-key storage means the cutover is a per-profile `publicUrl` change.
3. **Inventory every code path that writes assets** — all must funnel through `AssetMetadata`. A missed writer (cron, import, a second app on the DB) renders broken under `manifest-only` and never self-heals. Consider an auditor that flags asset rows updated without a complete manifest.
4. **Audit requested variant keys vs the registry** — a template requesting an unregistered variant 404s under the new path. Catch drift now; the single registry kills it.
5. **Preflight sweep for missing originals** — scan all `path` originals for existence. These are already-broken assets that fail backfill and, under `manifest-only`, lose even the fail-open floor. Quarantine/report before enabling writing.
6. **Preflight sweep for derived-only assets** (legacy rows whose only object is a derived image, no original). These can never re-ingest from an original. Plan to promote the best derived image to a *synthetic* original (`original_synthetic=true`, downscale-only variants) and exclude them from the completeness denominator.
7. **Confirm DB engine** (`jsonb` + CHECK on Postgres; `JSON` + VO-only integrity on MySQL/MariaDB).
8. **Identify the current variant mechanism** for the legacy fallback (`.{dim}/` controller, R2 `__variant.format`, or an image service).

---

## Step-by-step (per site)

### Step 1 — Install 2.0.0 in `dual` mode
- Bump to `^2.0` (`contenir/storage` ≥ the spec'd minor comes with it); mode = `dual`; new bucket configured alongside the old.
- **Verify:** the site renders exactly as before (legacy strategy, old bucket). If anything changed, stop — the legacy strategy isn't wired (Step 4). *Rollback:* revert composer.

### Step 2 — Add the schema (additive, non-destructive)
- Add `variants` (`jsonb`/`JSON`, nullable) + `width`, `height`, `mime`, `filesize` if missing.
- **Verify:** existing pages unaffected. *Rollback:* drop the columns.

### Step 3 — Define the `VariantRegistry`
- Declare every variant once (`name`, `formats`, `width`, `height`, `fit`, `enlarge`, `shrink`, `quality`); reconcile against the Step-4 audit. Record the registry **def-hash** — reconcile pins to it.

### Step 4 — Wire the legacy fallback strategy
- Implement/enable `LegacyVariantStrategyInterface` for this site's old mechanism (reads the **old bucket**). **Verify:** old assets render identically to 1.x. This is what makes the migration safe.

### Step 5 — Update templates (optional now, required before cutover)
- Replace `AssetSrcSet`/`AssetSizes` with `AssetPicture`/`AssetVariants`. They **fail open**: no manifest → original via legacy, so templates can migrate ahead of data without breaking.

### Step 6 — Enable manifest *writing* (new assets → new bucket)
- Turn on generate-on-write (synchronous-with-guards). New/edited uploads: generate variants, **verify by write-response checksum**, capture real dims, write versioned keys to the **new bucket**, persist manifest + original columns atomically (generate outside the lock; CAS commit under it).
- **Writes during migration go to the new bucket only**; old bucket stays read-only historical. Record migration-start (rollback forfeits assets created after it — warn).
- **Verify:** upload a test asset; confirm a complete manifest and the new path renders. *Rollback:* disable writing.

### Step 7 — Backfill existing assets (copy-forward)
- CLI reconcile **re-ingests from the original**: copy/verify the original into the new bucket (checksum-match), generate fresh variants with real dims, write the manifest. No probe-and-trust of old siblings.
- Idempotent, rate-limited, **resumable cursor**, atomic per-asset manifest write. Use **bucket-level listing diffed in memory**, not per-asset LIST, at fleet scale (cost).
- Derived-only assets → synthetic-original path (prereq 6).
- **Verify:** `asset:status` reports 100% complete manifests (quarantined/missing-original assets excluded with an alarm count). *Rollback:* mode flip back to `dual`/`legacy` (storage side already written to the new bucket — harmless; old bucket intact).

### Step 8 — Commit: `manifest-only`, then GC
- **`manifest-only` activation is a blocking precheck** — the mode change *refuses* unless zero non-excluded assets are incomplete. Not an eyeball of `--dry-run`.
- Legacy fallback now off; manifest authoritative.
- **Now — and only now — enable GC.** `reconcile --prune --dry-run` first, review, then prune. Confirm: prune is **no-op outside `manifest-only`**; **registry-version pin** refuses on a *mutating* registry change (additive is fine); **per-asset + fleet kill switch** (shared budget) is armed; **retention = hard floor (≥30d default) + reference check**, not age alone, and the reference set **unions the original**.
- Remove the legacy strategy/controller only after **≥ the page/HTML cache TTL** has elapsed since the flag flip ("stable").
- **Decommission the old bucket** only after the retention window — external/cached references to old-bucket URLs must have expired.
- **This is the irreversible step.** Do not skip the dry-run.

---

## Mitigation cheat-sheet

| Risk | Mitigation | Where |
|------|-----------|-------|
| `<picture>` 404 → broken image | Emit only manifest-vouched sources; always emit original `<img>` | Helpers (fail-open), Step 5 |
| Partial manifest treated as authoritative | Completeness = pure fn of (manifest, registry); incomplete stays on legacy | Steps 6–8 |
| `manifest-only` flipped before backfill done | Blocking precheck refuses the flip | Step 8 |
| Phantom variant (drift) | Single registry; requested-key audit | Steps 3–4 |
| GC deletes live legacy files | Prune no-op unless `manifest-only` | Step 8 |
| GC mass-delete on bad config | Registry-version pin (additive vs mutating) + per-asset & fleet kill switch | Step 8 |
| GC deletes cache-live versions | Retention = hard floor + reference check (unions original), not age alone | Step 8 |
| CLS / layout shift | Backfill writes real original + per-rendition dims; auto-orient on ingest | Steps 6–7 |
| Stale CDN/HTML cache at cutover | Versioned immutable keys; bake ≥ HTML TTL before removing legacy | Throughout, Step 8 |
| Missing original | Preflight sweep + quarantine; excluded from completeness | Prereq 5 |
| Derived-only asset | Synthetic original, downscale-only, excluded from denominator | Prereq 6, Step 7 |
| Writes during migration lost on rollback | New writes → new bucket only; rollback forfeits post-start assets (warned) | Step 6 |
| Untracked write path skips manifest | Inventory + auditor for manifest-less rows | Prereq 3 |
| Slow upload 504 / partial state | Generate outside lock with wall-time budget; commit nothing on exhaustion | Step 6 |

---

## Per-site checklist

- [ ] DB backed up; new bucket provisioned; old bucket read-only
- [ ] All write paths inventoried; requested-variant audit reconciled to registry
- [ ] Missing-original + derived-only preflight sweeps done
- [ ] DB engine confirmed; `contenir/storage` ≥ spec'd minor installed; mode = `dual`
- [ ] Schema columns added (nullable) — site renders unchanged
- [ ] Registry defined + def-hash recorded; legacy strategy wired (reads old bucket) and verified
- [ ] Templates migrated (fail-open confirmed)
- [ ] Manifest writing enabled; new writes → new bucket; test upload verified
- [ ] Backfill (copy-forward) run; `asset:status` = 100% complete (exclusions alarmed)
- [ ] `manifest-only` flip passed the blocking precheck
- [ ] GC dry-run reviewed; breaker + retention floor + fleet budget armed
- [ ] Legacy removed after ≥ HTML TTL; old bucket decommissioned after retention

---

## Rolling back

Every step before Step 8 is reversible: flip the mode flag back to `legacy`/`dual` and the site resolves via the old strategy against the **intact, read-only old bucket**. Schema columns are additive and droppable. Backfill only *adds* objects to the new bucket — the old bucket is never touched. **Only Step 8 (GC prune) is destructive**, and only after `manifest-only`; verify with `--dry-run` and keep the retention floor generous on first run. The one caveat: assets *created* after migration-start live only in the new bucket, so a rollback forfeits them (you're warned at flip time).
