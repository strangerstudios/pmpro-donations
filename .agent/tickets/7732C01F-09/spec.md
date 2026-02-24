# Cover processing fees checkbox at checkout

## Description

## Summary
Add a "Cover processing fees" checkbox at checkout so donors can optionally increase their donation to cover payment gateway fees, ensuring the organization receives the full intended donation amount.

## Product Context
- **Goal**: Many donors are willing to add a small amount to cover processing fees. This feature maximizes donation value for organizations.
- **User Flow**: Donor enters $50 donation → sees checkbox: "Add $1.75 to cover processing fees" → checks box → total updates to $51.75 → organization receives full $50 after fees.
- **Source**: Ideas meeting (Jason), High Priority

## Implementation
1. **Level settings** (`includes/level-settings.php`):
   - `cover_fees_enabled` (checkbox to enable feature per level)
   - `cover_fees_percentage` (default 2.9 for Stripe)
   - `cover_fees_flat` (default $0.30 for Stripe)

2. **Checkout** (`includes/checkout.php`):
   - Render checkbox below donation amount: "Add $X.XX to cover processing fees"
   - JavaScript calculates fee dynamically as donation amount changes
   - Fee formula: `fee = (donation + flat) / (1 - percentage/100) - donation`
   - Updates checkout total in real-time

3. **Order meta**:
   - Store original donation and fee separately: `donation_fee_covered`
   - Show fee breakdown on invoice

## Files
- `includes/checkout.php` — checkbox rendering, JS calculation
- `includes/level-settings.php` — fee configuration settings
- `includes/common.php` — fee calculation helper function

## Acceptance Criteria

1. 'Cover processing fees' checkbox appears at checkout (off by default)
2. Fee amount calculated and displayed dynamically as donation amount changes
3. When checked, total charge increased to cover fees using formula: fee = (donation + flat) / (1 - percentage/100) - donation
4. Fee percentage and flat amount configurable per level in level settings
5. Fee amount stored in order meta separately from donation amount (key: donation_fee_covered)
6. Invoice shows breakdown: Membership Cost, Donation, Processing Fee Covered
7. Works with dynamic amount changes (dropdown selection, custom input, button selection)
8. JavaScript calculation updates in real-time
9. Checkbox label includes calculated fee amount

## Metadata

- **Priority**: P1
- **Estimated Hours**: 20
- **Tags**: feature, checkout, javascript, gateway, phase-3
