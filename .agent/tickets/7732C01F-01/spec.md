# Merge PR #90 — PHP 8.5 deprecation fix for (double) casts

## Description

## Summary
Merge existing PR #90 by @dwanjuki (opened 2026-01-09) which fixes PHP 8.5 deprecation notices by replacing `(double)` casts with `(float)` in `checkout.php`.

## Details
- **PR**: #90, branch `fix-php-8.5-notices` → `dev`
- **Changes**: Replace 3 occurrences of `(double)` cast with `(float)` in `checkout.php`
- **Risk**: Low — trivial cast rename with no behavioral change. `(double)` and `(float)` are equivalent in PHP but `(double)` triggers deprecation in PHP 8.5+.

## Action
1. Confirm no merge conflicts with `dev-2.3`
2. Merge PR as-is
3. Verify no deprecation notices on PHP 8.5+
4. Quick smoke test of checkout validation (min/max amounts, price formatting)

## Acceptance Criteria

1. No PHP deprecation notices on PHP 8.5+
2. All (double) casts replaced with (float) throughout the plugin
3. Checkout validation still works (min/max amounts, price formatting)
4. No merge conflicts with dev-2.3 branch

## Metadata

- **Priority**: P2
- **Estimated Hours**: 8
- **Tags**: bug-fix, php-compat, pr-merge, phase-0
