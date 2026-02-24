# Donation reminder emails on configurable schedule

## Description

## Summary
Send periodic reminder emails to members encouraging them to donate again. Useful for free levels with donation-only functionality where re-engagement drives repeat donations.

## Product Context
- **Goal**: Increase repeat donation rates by sending timely, respectful reminders to past donors.
- **User Flow**: Member donates → X days later receives a friendly reminder email with donation link → can opt out of future reminders.
- **Issue**: #12

## Implementation
1. **Level settings** (`includes/level-settings.php`):
   - `reminder_interval` (none | monthly | quarterly | annually)

2. **Cron job**:
   - Register WP cron event to check for members due for reminders
   - Query: members who donated more than X days ago (based on interval)
   - Track last donation date per user in user meta

3. **Email**:
   - Reminder email template with donation link
   - Use PMPro's existing email infrastructure

4. **Opt-out**:
   - Respect unsubscribe/opt-out preference
   - Store opt-out in user meta

## Dependencies
Benefits from PMPro's existing email infrastructure.

## Files
- `includes/level-settings.php` — reminder interval setting
- New: `includes/reminders.php` — cron job, email logic
- Main plugin file — register cron hooks

## Acceptance Criteria

1. Configurable reminder interval per level (none, monthly, quarterly, annually)
2. Reminder emails sent on schedule via WP cron
3. Email links directly to donation checkout page for the relevant level
4. Tracks last donation date per user
5. Users can opt out of reminders
6. No reminders sent to opted-out users
7. Reminders only sent for levels with the feature enabled
8. Uses PMPro email infrastructure for sending

## Metadata

- **Priority**: P2
- **Estimated Hours**: 20
- **Tags**: feature, email, cron, phase-3
