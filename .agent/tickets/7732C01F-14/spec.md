# Integration Verification: PMPro Donations v2.3 end-to-end QA

## Description

## Summary
End-to-end integration verification for all PMPro Donations v2.3 features and bug fixes. This ticket covers manual QA of the complete user flow across all changes to ensure nothing breaks when all features are combined.

## Scope
Verify the integrated behavior of all v2.3 tickets working together:
- PR merges (PHP 8.5 fix, gateway fix, donation-only fix)
- Bug fixes (Pay by Check, whitespace)
- New features (buttons UI, cover fees, donor notes, reports)
- Experience improvements (donation-only language, email templates)

## Test Matrix
1. **Gateway coverage**: Test full checkout flow with Stripe, PayPal Express, Pay by Check
2. **Level types**: Free level + donation, paid level + donation, donation-only level
3. **User states**: New user, existing member, admin-created order
4. **Feature combinations**: Buttons + cover fees + donor note all enabled on same level
5. **PHP versions**: 8.1, 8.2, 8.5+

## Integration Checks
- Donation buttons UI + cover fees checkbox interact correctly (fee updates when button clicked)
- Donor note flows from checkout → order meta → email → invoice → CSV export → admin edit
- Donation-only level: proper language + level restoration + confirmation email all work together
- Reports correctly aggregate data from all gateway types and new meta fields
- Admin order creation includes donation amount, note, and fee fields

## Acceptance Criteria

1. Full checkout flow works with Stripe gateway: buttons → amount selection → cover fees → donor note → payment → confirmation → email
2. Full checkout flow works with PayPal Express gateway end-to-end
3. Full checkout flow works with Pay by Check gateway — donation amount saved correctly
4. Donation-only level checkout: donor keeps original membership, sees donation language, receives donation email
5. Free level with donations: gateways appear, $0 checkout still works when allowed
6. Donation buttons + cover fees + donor note all work simultaneously on same level
7. Reports page shows accurate data from all checkout types
8. CSV export includes donation amount, fee covered, and donor note columns
9. Admin can create order with donation amount for donation-enabled levels
10. No PHP deprecation notices on PHP 8.5+
11. All features work on mobile (responsive)
12. Keyboard accessibility verified for donation buttons

## Metadata

- **Priority**: P1
- **Estimated Hours**: 16
- **Tags**: qa, integration, testing, release
- **Dependencies**: 7732C01F-01, 7732C01F-02, 7732C01F-03, 7732C01F-04, 7732C01F-05, 7732C01F-06, 7732C01F-07, 7732C01F-08, 7732C01F-09, 7732C01F-12, 7732C01F-13
