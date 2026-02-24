# Fix donation amount storage bugs — Pay by Check gateway and whitespace

## Description

## Summary
Fix two related donation amount storage bugs that cause donation data to be lost or corrupted:

1. **Pay by Check gateway** (Issue #86, High Priority): Donation amount stored as 0 in order meta when using Pay by Check gateway. Works correctly with Stripe and other gateways.
2. **Whitespace in amounts** (Issue #89, Medium Priority): Donation amounts with trailing whitespace (e.g., "10 ") stored as strings with the space, causing exclusion from reports and numeric queries.

## Root Cause Investigation (Pay by Check)
The `pmprodon_store_donation_amount_in_order_meta()` function hooks `pmpro_after_checkout` and reads from `$_REQUEST['donation']`. Pay by Check may handle the checkout flow differently, clearing `$_REQUEST` before this hook fires. Compare the checkout flow for PBC vs Stripe to find where the donation value is lost.

## Implementation (Whitespace Fix)
Add `trim()` before storing donation amount in ALL storage paths:
1. `pmprodon_store_donation_amount_in_order_meta()` in `includes/checkout.php`
2. `pmprodon_pmpro_updated_order()` in `includes/admin.php` (admin order editing)
3. `pmprodon_pmpro_checkout_level()` where donation is read from `$_REQUEST`

Also verify the existing `preg_replace('/[^0-9\.]/', ...)` sanitization handles whitespace, or add explicit `trim()`.

## Files
- `includes/checkout.php` (primary — `pmprodon_store_donation_amount_in_order_meta`)
- `includes/admin.php` (admin order editing path)

## Acceptance Criteria

1. Donation amount correctly saved to order meta when using Pay by Check gateway
2. Donation amount appears in 'Additional Order Information' on admin order page for Pay by Check orders
3. Invoice shows correct donation breakdown for Pay by Check orders
4. No regression with Stripe, PayPal Express, or other gateways
5. Donation amounts are trimmed before storage (no leading/trailing whitespace)
6. Reports correctly count donations that previously had whitespace
7. Admin-entered donation amounts also trimmed
8. All donation storage paths sanitize input consistently

## Metadata

- **Priority**: P1
- **Estimated Hours**: 12
- **Tags**: bug-fix, checkout, gateway, data-integrity, phase-1
