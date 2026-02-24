# Guest donations without account creation

## Description

## Summary
Allow donation-only checkout without creating a WordPress user account. For pure donation pages, requiring account creation adds friction that reduces conversion. This is a complex feature flagged as a v3.0 candidate.

## Product Context
- **Goal**: Remove the account creation barrier for one-time donors, increasing donation conversion rates.
- **User Flow**: Visitor clicks "Donate" → selects amount → sees option to donate as guest → enters email only → pays → receives confirmation email (no account details) → can access confirmation via unique key.
- **Issue**: #22

## Implementation
1. **Level settings**: New `allow_guest_donations` checkbox
2. **Checkout flow**:
   - Dropdown on checkout: "Donate as Guest" or "Create Account"
   - Guest flow: collect email only, skip registration fields
   - Create temp user, process payment, delete user after confirmation
   - Generate random key for confirmation page access
3. **Confirmation**: Custom confirmation email without account details
4. **Edge cases**: Logged-in users don't see the guest/account toggle

## Risk
High complexity — touches core PMPro user creation flow. Requires careful testing of the temp user creation/deletion cycle and its interaction with gateway webhooks, recurring payments, etc.

## Files
- `includes/checkout.php` — guest flow logic
- `includes/donation-only-level.php` — guest support
- `includes/level-settings.php` — new setting

## Acceptance Criteria

1. Guest can donate with only email address (no username/password required)
2. No persistent WordPress user created for guest donations
3. Payment processed and order recorded correctly
4. Confirmation page accessible via unique key without user account
5. Confirmation email works without account details
6. Logged-in users don't see the guest/account toggle
7. Level setting to enable/disable guest donations per level
8. Skip registration fields for guest checkout flow
9. No interference with existing logged-in user checkout flow

## Metadata

- **Priority**: P2
- **Estimated Hours**: 32
- **Tags**: feature, checkout, user-flow, phase-3, v3.0-candidate
