# Donation-only level experience overhaul — language, UI, and confirmation

## Description

## Summary
Comprehensively improve the donation-only level experience so it feels like a proper donation flow rather than a membership checkout hack. This consolidates Issues #62 (donation-specific confirmation) and Ideas Meeting item "Better donations without a level" into a single cohesive effort.

## Product Context
- **Goal**: When a user checks out for a donation-only level, every piece of text, email, and UI element should reference "donation" — not "membership."
- **User Flow**: Donor selects donation-only level → checkout page says "Make a Donation" (not "Select a Membership") → confirmation says "Thank you for your donation" → email references donation → account page does NOT show donation-only level as an active membership.
- **Components**: Checkout text filtering, confirmation page, email templates, account page filtering.

## Implementation
1. **Checkout text** (`includes/checkout.php`):
   - Filter `pmpro_checkout_page_title` and other checkout text hooks
   - Replace membership language with donation language for donation-only levels
   - Hide membership-specific UI elements (expiration dates, etc.)

2. **Confirmation page** (`includes/donation-only-level.php`):
   - Filter `pmpro_confirmation_message` for donation-only levels
   - Show "Thank you for your donation" instead of "your membership is now active"
   - Use existing `confirmation_message` level setting as starting point

3. **Email integration** (`includes/checkout.php`):
   - Filter `pmpro_email_data` to provide donation-specific subject lines and body content
   - Add/filter email template for donation-only checkouts
   - Ensure no "your membership level has changed" messaging

4. **Account page**:
   - Don't show donation-only level on membership account page
   - Filter relevant account page hooks

## Integration Points
- **Receives from**: 7732C01F-03 (donation-only level restoration fix must be in place first)
- **Provides to**: 7732C01F-12 (email templates per level builds on this foundation)

## Files
- `includes/donation-only-level.php` — primary changes
- `includes/checkout.php` — text filtering, email data

## Acceptance Criteria

1. Checkout page uses 'donation' language instead of 'membership' for donation-only levels
2. Confirmation page says 'Thank you for your donation' instead of 'your membership is now active'
3. Checkout email uses donation-appropriate language and subject line for donation-only levels
4. Regular donation levels (donation + membership) still show standard membership messaging
5. Donation-only level not shown on membership account page
6. Existing member's level not affected by donation-only checkout (verified with 7732C01F-03 fix)
7. No confusing 'your membership level has changed' messaging anywhere in the flow
8. Site owner can customize the donation confirmation message per level
9. Hide membership-specific UI elements (expiration dates) for donation-only levels

## Metadata

- **Priority**: P1
- **Estimated Hours**: 20
- **Tags**: enhancement, donation-only, checkout, email, ux, phase-2, phase-3
- **Dependencies**: 7732C01F-03
