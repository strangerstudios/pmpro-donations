# Admin donation amount field when creating/editing orders

## Description

## Summary
Add a donation amount field to the admin "Add New Order" and "Add Member" pages so administrators can specify donation amounts without going through front-end checkout.

## Product Context
- **Goal**: Admins should be able to record offline donations or manually create donation orders from the WordPress admin.
- **User Flow**: Admin goes to Add New Order → selects a level with donations enabled → donation amount field appears → enters amount → order created with donation recorded in order meta.

## Implementation
- Hook into `pmpro_after_order_settings` on the add-new-order admin page
- Check if existing edit hooks in `admin.php` already handle this — may need only minor additions
- Show donation amount field only when the selected level has donations enabled
- Save donation to order meta on order creation
- Integrate with "Add Member" admin page if applicable

## Files
- `includes/admin.php`

## Acceptance Criteria

1. Donation amount field appears when creating new orders in admin for levels with donations enabled
2. Field only shows for levels with donations enabled (hidden otherwise)
3. Donation amount saved to order meta on order creation
4. Works with 'Add Member' admin page if applicable
5. Field pre-populates when editing existing orders with donations

## Metadata

- **Priority**: P2
- **Estimated Hours**: 10
- **Tags**: enhancement, admin, phase-2
