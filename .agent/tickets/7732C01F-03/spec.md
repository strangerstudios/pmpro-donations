# Merge PR #85 — Fix donation-only level replacing user's membership

## Description

## Summary
Merge PR #85 by @ipokkel (opened 2025-04-18) which fixes the critical bug where users with existing memberships lose their level after checking out for a donation-only level. Resolves Issues #72 and #91.

## Product Context
- **Goal**: Users should be able to make donations via donation-only levels without losing their existing membership.
- **User Flow**: Gold member clicks "Donate" → checks out via donation-only level → donation is processed → user still has Gold membership.

## Details
- **PR**: #85, branch `keep-level` → `dev`
- **Resolves**: Issue #72 (member level changes to donation level), Issue #91 (users keep donation level)
- **Changes**: Complete rewrite of `donation-only-level.php`
  - Stores user's previous levels in user meta before checkout
  - After checkout, finds which level was in the same group as donation level
  - Cancels donation-only level via `pmpro_cancelMembershipLevel()`
  - Restores original level via `pmpro_changeMembershipLevel()` with full level data

## Review Notes
- Old approach (DELETE SQL query) was fragile; new approach using PMPro API is much better
- Verify: does `pmpro_cancelMembershipLevel()` trigger unwanted side effects (emails, hooks)?
- The stored `pmprodon_previous_levels` user meta should be cleaned up even on failure paths
- **Test matrix**: single level group, multiple level groups, no level groups, recurring subscription levels

## Provides To
- 7732C01F-06 (Donation-only experience overhaul) depends on this level restoration logic being solid

## Acceptance Criteria

1. User with Gold membership donates via donation-only level and keeps Gold membership
2. Donation-only level in same level group as user's level — user keeps original level
3. Donation-only level in separate level group — user keeps original level
4. Recurring subscription not cancelled at gateway after donation-only checkout
5. No orphaned pmprodon_previous_levels user meta after checkout (cleaned up on success and failure)
6. pmpro_cancelMembershipLevel() does not trigger unwanted emails or hooks during restoration
7. Works correctly with multiple level groups

## Metadata

- **Priority**: P0
- **Estimated Hours**: 16
- **Tags**: bug-fix, pr-merge, donation-only, checkout, phase-0, phase-1
