# Donor note field at checkout with invoice and email integration

## Description

## Summary
Add an optional "Donation Note" text field at checkout that donors can use to add a message (e.g., "In memory of..." or "For the purpose of..."). The note should flow through to invoices, confirmation pages, emails, admin order editing, and CSV exports.

## Product Context
- **Goal**: Allow donors to attach a personal note or designation to their donation.
- **User Flow**: Donor sees optional "Donation Note" textarea below donation amount → enters note → note appears on confirmation, invoice, and in admin order details.

## Implementation
1. **Level settings** (`includes/level-settings.php`):
   - New setting: `donation_note_enabled` (checkbox, optional per level)
   - New setting: `donation_note_label` (custom label text, default "Donation Note")

2. **Checkout** (`includes/checkout.php`):
   - Render textarea below donation amount field when enabled for the level
   - Store note in order meta with key `donation_note`
   - Display on confirmation page

3. **Email integration**:
   - Register `!!donation_note!!` as an email template variable
   - Populate variable from order meta

4. **Admin** (`includes/admin.php`):
   - Display note on admin order edit page
   - Allow editing the note from admin
   - Include in CSV export

## Files
- `includes/level-settings.php` — new settings
- `includes/checkout.php` — render field, store, display on confirmation
- `includes/admin.php` — admin display/edit, CSV export

## Acceptance Criteria

1. Optional per-level setting to enable donation note field
2. Custom label text configurable per level (default: 'Donation Note')
3. Text field appears at checkout below donation amount when enabled
4. Note stored in order meta with key donation_note
5. Note appears on order invoice page
6. !!donation_note!! email variable works in email templates
7. Note visible and editable in admin order edit page
8. Note included in CSV export (reports)
9. Note appears on checkout confirmation page

## Metadata

- **Priority**: P1
- **Estimated Hours**: 16
- **Tags**: feature, checkout, admin, email, phase-2
