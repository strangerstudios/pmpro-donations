# Per-level email and confirmation template overrides for donations

## Description

## Summary
Allow different email confirmation templates and confirmation page content per level type (donation vs membership vs purchase). This extends the donation-only experience overhaul with a more generic template override system.

## Product Context
- **Goal**: Users increasingly use PMPro for generic purchases, not just memberships. Different level types need different communication.
- **User Flow**: Admin configures a donation level → selects "Donation" email template → sets custom confirmation content → donors receive donation-specific emails with `!!donation!!` and `!!donation_note!!` variables.
- **Source**: Ideas meeting (Kim)

## Implementation
1. **Level settings** (`includes/level-settings.php`):
   - New setting: `email_template_override` — allows selecting a different email template
   - Leverages existing `confirmation_message` setting (already per-level)

2. **Email filtering**:
   - Hook `pmpro_email_filter` to swap template when checkout level has donations
   - Register donation-specific email variables: `!!donation!!`, `!!donation_note!!`

3. **Compatibility**:
   - Compatible with pmpro-email-templates add-on if installed
   - May need coordination with Email Templates add-on for full functionality

## Integration Points
- **Receives from**: 7732C01F-12 (donation-only experience overhaul provides the base donation language filtering)
- **Receives from**: 7732C01F-06 (donor note field provides the `!!donation_note!!` variable)

## Files
- `includes/level-settings.php` — template override setting
- `includes/checkout.php` — email filter hooks

## Acceptance Criteria

1. Donation-only levels can have fully customized confirmation emails via template override
2. Confirmation page content customizable per level
3. Email uses !!donation!! and !!donation_note!! template variables
4. Compatible with Email Templates add-on if installed
5. Level setting to select email template override
6. Regular membership levels unaffected by template override system
7. Template override only applies when explicitly configured per level

## Metadata

- **Priority**: P2
- **Estimated Hours**: 16
- **Tags**: feature, email, admin, phase-3
- **Dependencies**: 7732C01F-12, 7732C01F-06
